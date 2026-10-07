<?php

namespace App\Http\Controllers;

use App\Actions\Owner\ComputeOwnerDashboard;
use App\Domain\Booking\Actions\MarkNoShow;
use App\Domain\Reminders\Jobs\SendReminderJob;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Reminder;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected MarkNoShow $markNoShow
    ) {}

    /**
     * Display the owner KPI dashboard with real analytics.
     */
    public function index(Request $request, ComputeOwnerDashboard $computeDashboard): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isEmployee()) {
            return $this->employeeDashboard($request, $user);
        }

        if ($user !== null && $user->isClient()) {
            return $this->clientDashboard($user);
        }

        $business = $user?->ownedBusinesses()->first() ?? Business::query()->first();

        $businessId = $business?->id;
        $dashboard = $computeDashboard->handle($business);

        // Appointments for ledger
        $appointments = Booking::query()
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->with(['client', 'service', 'employee', 'location'])
            ->orderBy('start_at', 'desc')
            ->limit(30)
            ->get()
            ->map(fn ($b) => $this->ledgerItem($b));

        // Active Waitlist Queue
        $waitlist = WaitlistEntry::query()
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->with(['client', 'service'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($w) => [
                'id' => $w->id,
                'client_name' => $w->client->name,
                'service_name' => $w->service->name,
                'preferred_date' => $w->preferred_date->toDateString(),
                'preferred_window' => ($w->preferred_time_from ?? '09:00').' - '.($w->preferred_time_to ?? '18:00'),
                'status' => $w->status,
                'offer_expires_at' => $w->offer_expires_at?->toIso8601String(),
            ]);

        return Inertia::render('dashboard', [
            'business' => [
                'id' => $business?->id,
                'name' => $business->name ?? 'Aurora Hair Studio',
                'city' => $business->city ?? 'Lisbon',
            ],
            'kpis' => $dashboard['kpis'],
            'funnel' => $dashboard['funnel'],
            'risk_distribution' => $dashboard['risk_distribution'],
            'appointments' => $appointments,
            'waitlist' => $waitlist,
        ]);
    }

    /**
     * Client branch of /dashboard — personal booking history, spend and reviews.
     */
    private function clientDashboard(User $user): Response
    {
        $bookings = Booking::query()
            ->where('client_user_id', $user->id)
            ->with(['service', 'location', 'business', 'employee', 'review'])
            ->orderBy('start_at', 'desc')
            ->get();

        $upcoming = $bookings
            ->filter(fn ($b) => $b->start_at->isFuture()
                && ($b->status === BookingStatus::Pending || $b->status === BookingStatus::Confirmed))
            ->sortBy('start_at')
            ->values();

        $completed = $bookings->where('status', BookingStatus::Completed);
        $past = $bookings
            ->filter(fn ($b) => $b->status->isFinal() || ! $b->start_at->isFuture())
            ->values();

        $item = fn ($b) => [
            'id' => $b->id,
            'reference_code' => $b->reference_code,
            'business_name' => $b->business->name,
            'service_name' => $b->service->name,
            'location_name' => $b->location->name,
            'employee_name' => $b->employee->name ?? '',
            'start_at' => $b->start_at->toIso8601String(),
            'end_at' => $b->end_at->toIso8601String(),
            'status' => $b->status->value,
            'total_amount' => (float) $b->total_amount,
            'cancellation_fee' => (float) ($b->cancellation_fee ?? 0),
            'deposit_status' => $b->deposit_status ?? 'none',
            'rating' => $b->review?->rating,
            'reviewable' => $b->status === BookingStatus::Completed && $b->review === null,
        ];

        return Inertia::render('client/dashboard', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'summary' => [
                'upcoming' => $upcoming->count(),
                'visits' => $completed->count(),
                'total_spent' => round((float) $completed->sum('total_amount'), 2),
                'cancellation_fees' => round((float) $bookings->sum('cancellation_fee'), 2),
            ],
            'upcoming' => $upcoming->map($item),
            'past' => $past->map($item)->values(),
        ]);
    }

    /**
     * Employee branch of /dashboard — personal schedule for the selected day.
     */
    private function employeeDashboard(Request $request, User $user): Response
    {
        $day = today();
        if ($request->filled('date')) {
            try {
                $day = Carbon::parse($request->query('date'))->startOfDay();
            } catch (\Throwable) {
                $day = today();
            }
        }

        $appointments = Booking::query()
            ->where('employee_user_id', $user->id)
            ->whereBetween('start_at', [$day, $day->copy()->endOfDay()])
            ->with(['client', 'service', 'employee', 'location'])
            ->orderBy('start_at')
            ->get();

        $countStatus = fn (BookingStatus $status) => $appointments->where('status', $status)->count();

        return Inertia::render('employee/dashboard', [
            'profile' => [
                'name' => $user->name,
                'rating_average' => (float) ($user->rating_average ?? 0),
                'rating_count' => (int) ($user->rating_count ?? 0),
                'locations' => $user->locations()
                    ->wherePivot('is_active', true)
                    ->get()
                    ->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'city' => $l->city]),
                'services' => $user->services()
                    ->get()
                    ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            ],
            'selected_date' => $day->toDateString(),
            'today' => now()->toDateString(),
            'summary' => [
                'total' => $appointments->count(),
                'completed' => $countStatus(BookingStatus::Completed),
                'no_show' => $countStatus(BookingStatus::NoShow),
                'cancelled' => $countStatus(BookingStatus::Cancelled),
                'remaining' => $appointments->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])->count(),
                'week_total' => Booking::query()
                    ->where('employee_user_id', $user->id)
                    ->where('start_at', '>=', now())
                    ->where('start_at', '<=', now()->addDays(7))
                    ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                    ->count(),
            ],
            'appointments' => $appointments->map(fn ($b) => $this->ledgerItem($b)),
        ]);
    }

    /**
     * Shared ledger shape used by both owner and employee dashboards.
     *
     * @return array<string, mixed>
     */
    private function ledgerItem(Booking $b): array
    {
        return [
            'id' => $b->id,
            'reference_code' => $b->reference_code,
            'client_name' => $b->client->name,
            'client_phone' => $b->client->phone ?? '',
            'service_name' => $b->service->name,
            'employee_name' => $b->employee->name ?? 'Staff',
            'location_name' => $b->location->name,
            'start_at' => $b->start_at->toIso8601String(),
            'end_at' => $b->end_at->toIso8601String(),
            'status' => $b->status->value,
            'risk_score' => (float) ($b->risk_score ?? 0.15),
            'risk_tier' => $b->risk_tier ?? 'low',
            'deposit_status' => $b->deposit_status ?? 'none',
            'deposit_amount' => (float) ($b->deposit_amount ?? 0),
            'total_amount' => (float) $b->total_amount,
        ];
    }

    /**
     * Mark an appointment as checked in / completed.
     */
    public function checkIn(Booking $booking): JsonResponse
    {
        Gate::authorize('update', $booking);

        $booking->update([
            'status' => BookingStatus::Completed,
            'completed_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Appointment {$booking->reference_code} marked as completed.",
            'booking_status' => $booking->status->value,
        ]);
    }

    /**
     * Mark an appointment as No-Show and trigger deposit forfeiture.
     */
    public function markNoShow(Booking $booking): JsonResponse
    {
        Gate::authorize('update', $booking);

        $updated = $this->markNoShow->execute($booking);

        return response()->json([
            'status' => 'success',
            'message' => "Appointment {$booking->reference_code} marked as No-Show. Deposit forfeited.",
            'booking_status' => $updated->status->value,
            'deposit_status' => $updated->deposit_status,
        ]);
    }

    /**
     * Dispatch WhatsApp reminder immediately.
     */
    public function sendReminder(Booking $booking): JsonResponse
    {
        Gate::authorize('update', $booking);

        $reminder = Reminder::create([
            'booking_id' => $booking->id,
            'channel' => 'whatsapp',
            'type' => 'manual',
            'scheduled_for' => now(),
            'delivery_status' => 'scheduled',
        ]);

        dispatch(new SendReminderJob($reminder));

        return response()->json([
            'status' => 'success',
            'message' => "WhatsApp reminder triggered for {$booking->reference_code}.",
        ]);
    }
}
