<?php

namespace Tests\Feature\Owner;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Location;
use App\Models\Reminder;
use App\Models\Service;
use App\Models\User;
use App\Models\WaitlistEntry;
use Database\Seeders\DemoBusinessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_dashboard_reports_real_metrics_for_demo_seed(): void
    {
        $this->seed(DemoBusinessSeeder::class);

        $owner = User::query()->where('email', 'mateo@crownblade.test')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('business.name', 'Crown & Blade Barbershop')
                ->where('kpis.baseline_no_show_rate', fn ($value) => $value > 12)
                ->where('kpis.no_show_rate', fn ($value) => $value > 0 && $value < 10)
                ->where('kpis.reduction_percentage', fn ($value) => $value > 0)
                ->where('kpis.recovered_revenue', fn ($value) => $value > 0)
                ->where('kpis.refilled_slots_count', fn ($value) => $value > 20)
                ->where('kpis.admin_hours_saved', fn ($value) => $value > 0)
                ->where('funnel.sent', fn ($value) => $value > 0)
                ->where('risk_distribution.medium', fn ($value) => $value > 0)
                ->where('risk_distribution.high', fn ($value) => $value > 0)
                ->has('appointments')
                ->has('waitlist'));
    }

    public function test_dashboard_metrics_are_zeroed_when_no_business_exists(): void
    {
        $user = User::factory()->owner()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('kpis.no_show_rate', 0)
                ->where('kpis.recovered_revenue', 0)
                ->where('kpis.refilled_slots_count', 0)
                ->where('funnel.sent', 0)
                ->where('risk_distribution.low', 0));
    }

    public function test_funnel_risk_and_recovery_metrics_come_from_real_rows(): void
    {
        $owner = User::factory()->owner()->create();
        $business = Business::factory()->create(['owner_user_id' => $owner->id]);
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create(['business_id' => $business->id]);

        $bookings = [];
        foreach ([
            ['risk_score' => 0.20, 'status' => BookingStatus::Confirmed],
            ['risk_score' => 0.50, 'status' => BookingStatus::Confirmed],
            ['risk_score' => 0.70, 'status' => BookingStatus::Confirmed],
            ['risk_score' => null, 'status' => BookingStatus::Confirmed],
            ['risk_score' => null, 'risk_tier' => 'high', 'status' => BookingStatus::NoShow],
        ] as $attributes) {
            $bookings[] = Booking::factory()->create([
                ...$attributes,
                'business_id' => $business->id,
                'service_id' => $service->id,
                'location_id' => $location->id,
            ]);
        }

        [$delivered, $read, $confirmed, $scheduledOnly, $rescheduled, $cancelled, $noShowReminder] = [
            ['delivery_status' => 'delivered', 'sent_at' => now()],
            ['delivery_status' => 'read', 'sent_at' => now()],
            ['delivery_status' => 'confirmed', 'sent_at' => now()],
            ['delivery_status' => 'scheduled', 'sent_at' => null],
            ['delivery_status' => 'delivered', 'sent_at' => now(), 'interactive_action' => 'reschedule'],
            ['delivery_status' => 'delivered', 'sent_at' => now(), 'interactive_action' => 'cancel'],
            ['delivery_status' => 'delivered', 'sent_at' => now()],
        ];

        foreach ([
            [$bookings[0], $delivered],
            [$bookings[1], $read],
            [$bookings[2], $confirmed],
            [$bookings[3], $scheduledOnly],
            [$bookings[0], $rescheduled],
            [$bookings[1], $cancelled],
            [$bookings[4], $noShowReminder],
        ] as [$booking, $attributes]) {
            Reminder::create([
                'booking_id' => $booking->id,
                'channel' => 'whatsapp',
                'type' => '24h',
                'scheduled_for' => now()->subHour(),
                ...$attributes,
            ]);
        }

        foreach ([40.00, 60.00] as $price) {
            $fillerService = Service::factory()->create([
                'business_id' => $business->id,
                'price' => $price,
            ]);
            WaitlistEntry::create([
                'business_id' => $business->id,
                'client_user_id' => User::factory()->client()->create()->id,
                'service_id' => $fillerService->id,
                'preferred_date' => now()->addDay()->toDateString(),
                'status' => 'claimed',
                'claim_token' => str()->random(40),
            ]);
        }

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('kpis.no_show_rate', fn ($value) => $value == 20)
                ->where('kpis.recovered_revenue', fn ($value) => $value == 100)
                ->where('kpis.refilled_slots_count', 2)
                ->where('kpis.admin_hours_saved', fn ($value) => $value > 0)
                ->where('funnel.sent', 6)
                ->where('funnel.delivered', 6)
                ->where('funnel.read', 2)
                ->where('funnel.confirmed', 1)
                ->where('funnel.rescheduled', 1)
                ->where('funnel.cancelled', 1)
                ->where('funnel.no_show', 1)
                ->where('risk_distribution.low', 2)
                ->where('risk_distribution.medium', 1)
                ->where('risk_distribution.high', 2));
    }
}
