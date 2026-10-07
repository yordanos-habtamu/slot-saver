<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Database\Seeders\DemoBusinessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_business_seeder_generates_longitudinal_dataset(): void
    {
        $this->seed(DemoBusinessSeeder::class);

        $business = Business::where('slug', 'crown-and-blade')->first();
        $this->assertNotNull($business);

        $totalBookings = Booking::where('business_id', $business->id)->count();
        $this->assertGreaterThanOrEqual(700, $totalBookings);

        $refilledWaitlist = WaitlistEntry::where('business_id', $business->id)
            ->where('status', 'claimed')
            ->count();
        $this->assertGreaterThan(20, $refilledWaitlist);
    }

    public function test_no_show_rate_drops_longitudinally_from_baseline_to_slotsaver(): void
    {
        $this->seed(DemoBusinessSeeder::class);

        $business = Business::where('slug', 'crown-and-blade')->first();

        // Baseline era: 26 to 22 weeks ago
        $baselineStart = Carbon::now()->subWeeks(26)->startOfWeek();
        $baselineEnd = Carbon::now()->subWeeks(22)->endOfWeek();

        $baselineTotal = Booking::where('business_id', $business->id)
            ->whereBetween('start_at', [$baselineStart, $baselineEnd])
            ->count();

        $baselineNoShows = Booking::where('business_id', $business->id)
            ->whereBetween('start_at', [$baselineStart, $baselineEnd])
            ->where('status', BookingStatus::NoShow->value)
            ->count();

        $baselineRate = $baselineTotal > 0 ? ($baselineNoShows / $baselineTotal) * 100 : 0;

        // SlotSaver era: 16 to 1 weeks ago
        $slotsaverStart = Carbon::now()->subWeeks(16)->startOfWeek();
        $slotsaverEnd = Carbon::now()->subWeeks(1)->endOfWeek();

        $slotsaverTotal = Booking::where('business_id', $business->id)
            ->whereBetween('start_at', [$slotsaverStart, $slotsaverEnd])
            ->count();

        $slotsaverNoShows = Booking::where('business_id', $business->id)
            ->whereBetween('start_at', [$slotsaverStart, $slotsaverEnd])
            ->where('status', BookingStatus::NoShow->value)
            ->count();

        $slotsaverRate = $slotsaverTotal > 0 ? ($slotsaverNoShows / $slotsaverTotal) * 100 : 0;

        // Assert statistical reduction
        $this->assertGreaterThan(12.0, $baselineRate, 'Baseline no-show rate should be >12%');
        $this->assertLessThan(10.0, $slotsaverRate, 'SlotSaver era no-show rate should be <10%');
        $this->assertGreaterThan($slotsaverRate, $baselineRate, 'Baseline rate must be higher than SlotSaver rate');
    }

    public function test_metabase_export_json_schema_and_queries_are_valid(): void
    {
        $exportPath = base_path('analytics/metabase-dashboards/export.json');
        $this->assertFileExists($exportPath);

        $jsonContent = file_get_contents($exportPath);
        $data = json_decode($jsonContent, true);

        $this->assertIsArray($data);
        $this->assertEquals('SlotSaver: Executive Attendance & Revenue Recovery Analytics', $data['dashboard']['name']);
        $this->assertCount(5, $data['dashboard']['cards']);

        foreach ($data['dashboard']['cards'] as $card) {
            $this->assertArrayHasKey('id', $card);
            $this->assertArrayHasKey('name', $card);
            $this->assertArrayHasKey('dataset_query', $card);
            $this->assertNotEmpty($card['dataset_query']['native']['query']);
        }
    }
}
