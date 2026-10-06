<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Enums\HistoryAction;
use App\Enums\UserRole;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancellation_fee_is_charged_inside_the_free_cancellation_window(): void
    {
        $booking = $this->bookingWithService(cancellationFee: 40, freeCancellationHours: 24);
        $start = $booking->start_at;

        $this->assertSame('40.00', $booking->cancellationFeeDue($start->copy()->subHours(3)));
    }

    public function test_cancellation_is_free_outside_the_free_cancellation_window(): void
    {
        $booking = $this->bookingWithService(cancellationFee: 40, freeCancellationHours: 24);
        $start = $booking->start_at;

        $this->assertSame('0.00', $booking->cancellationFeeDue($start->copy()->subHours(48)));
    }

    public function test_service_without_a_free_cancellation_window_always_charges(): void
    {
        $booking = $this->bookingWithService(cancellationFee: 25, freeCancellationHours: null);
        $start = $booking->start_at;

        $this->assertSame('25.00', $booking->cancellationFeeDue($start->copy()->subDays(7)));
    }

    public function test_service_without_a_cancellation_fee_is_never_charged(): void
    {
        $booking = $this->bookingWithService(cancellationFee: 0, freeCancellationHours: null);

        $this->assertSame('0.00', $booking->cancellationFeeDue());
    }

    public function test_only_open_bookings_hold_capacity(): void
    {
        $this->assertTrue(BookingStatus::Pending->holdsCapacity());
        $this->assertTrue(BookingStatus::Confirmed->holdsCapacity());
        $this->assertFalse(BookingStatus::Cancelled->holdsCapacity());
        $this->assertFalse(BookingStatus::Completed->holdsCapacity());
        $this->assertFalse(BookingStatus::NoShow->holdsCapacity());
    }

    public function test_history_actions_cover_the_client_ledger(): void
    {
        $this->assertSame(
            ['booked', 'cancelled', 'rescheduled'],
            array_map(fn (HistoryAction $action): string => $action->value, HistoryAction::cases()),
        );
    }

    public function test_client_is_the_default_role(): void
    {
        $this->assertSame(UserRole::Client, Booking::factory()->create()->client->role);
    }

    private function bookingWithService(float $cancellationFee, ?int $freeCancellationHours): Booking
    {
        $booking = Booking::factory()->create();
        $start = Carbon::now()->addWeek()->setTime(12, 0);

        $booking->service()->update([
            'cancellation_fee' => $cancellationFee,
            'free_cancellation_hours' => $freeCancellationHours,
        ]);

        $booking->update([
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes($booking->duration_minutes),
        ]);

        return $booking->fresh(['service']);
    }
}
