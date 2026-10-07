<?php

namespace Tests\Feature;

use App\Domain\Risk\DepositPolicy;
use App\Domain\Risk\RiskScoreClient;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RiskServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_risk_score_client_calls_microservice_and_parses_response(): void
    {
        Http::fake([
            '*/score' => Http::response([
                'risk_score' => 0.7420,
                'risk_tier' => 'high',
                'requires_deposit' => true,
                'suggested_deposit_amount' => 15.00,
                'top_risk_factors' => [
                    ['factor' => 'prior_no_shows', 'impact' => '+0.45', 'description' => 'Missed past visits.'],
                ],
                'latency_ms' => 3.2,
            ], 200),
        ]);

        $client = User::factory()->client()->create();
        $startAt = Carbon::now()->addDays(3);

        $riskClient = new RiskScoreClient;
        $result = $riskClient->scoreBooking(
            clientUserId: $client->id,
            startAt: $startAt,
            durationMinutes: 45,
            servicePrice: 50.0,
            depositPaid: false
        );

        $this->assertSame(0.7420, $result['risk_score']);
        $this->assertSame('high', $result['risk_tier']);
        $this->assertTrue($result['requires_deposit']);
        $this->assertSame(15.00, $result['suggested_deposit_amount']);
        $this->assertFalse($result['fallback_used']);
    }

    public function test_risk_score_client_falls_back_to_local_heuristics_when_service_is_down(): void
    {
        Http::fake([
            '*/score' => Http::response([], 500),
        ]);

        $client = User::factory()->client()->create();
        $startAt = Carbon::now()->addDays(5);

        $riskClient = new RiskScoreClient;
        $result = $riskClient->scoreBooking(
            clientUserId: $client->id,
            startAt: $startAt,
            durationMinutes: 30,
            servicePrice: 40.0,
        );

        $this->assertTrue($result['fallback_used']);
        $this->assertIsFloat($result['risk_score']);
        $this->assertIsString($result['risk_tier']);
    }

    public function test_deposit_policy_enforces_deposits_for_high_risk_and_waives_for_loyal_vip(): void
    {
        $policy = new DepositPolicy;
        $service = Service::factory()->create([
            'price' => 60.00,
            'booking_fee' => 0.00,
        ]);

        $highRiskAssessment = [
            'risk_score' => 0.82,
            'risk_tier' => 'high',
            'requires_deposit' => true,
            'suggested_deposit_amount' => 15.00,
        ];

        // 1. Regular client with high risk assessment -> deposit required
        $regularClient = User::factory()->client()->create();
        $evaluation = $policy->evaluate($service, $regularClient, $highRiskAssessment);

        $this->assertTrue($evaluation['deposit_required']);
        $this->assertSame(15.00, $evaluation['amount']);

        // 2. VIP client with 5 completed visits and 0 no-shows -> waiver applied
        $vipClient = User::factory()->client()->create();
        Booking::factory()->count(5)->create([
            'client_user_id' => $vipClient->id,
            'status' => BookingStatus::Completed,
        ]);

        $vipEvaluation = $policy->evaluate($service, $vipClient, $highRiskAssessment);
        $this->assertFalse($vipEvaluation['deposit_required']);
        $this->assertStringContainsString('VIP loyalty waiver', $vipEvaluation['reason']);
    }

    public function test_export_risk_features_command_generates_csv(): void
    {
        $business = Business::factory()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create(['business_id' => $business->id]);
        $client = User::factory()->client()->create();

        // 1 Completed and 1 No-Show
        Booking::factory()->create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'service_id' => $service->id,
            'client_user_id' => $client->id,
            'status' => BookingStatus::Completed,
        ]);

        Booking::factory()->create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'service_id' => $service->id,
            'client_user_id' => $client->id,
            'status' => BookingStatus::NoShow,
        ]);

        $testCsvPath = storage_path('app/risk/test_features.csv');
        if (File::exists($testCsvPath)) {
            File::delete($testCsvPath);
        }

        $this->artisan("risk:export-features --output={$testCsvPath}")
            ->assertExitCode(0);

        $this->assertTrue(File::exists($testCsvPath));
        $content = File::get($testCsvPath);
        $this->assertStringContainsString('lead_time_hours', $content);
        $this->assertStringContainsString('prior_no_shows', $content);
        $this->assertStringContainsString('no_show', $content);

        File::delete($testCsvPath);
    }
}
