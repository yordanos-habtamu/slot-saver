<?php

namespace App\Http\Controllers;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Data\BookingData;
use App\Domain\Booking\Exceptions\SlotAlreadyBookedException;
use App\Domain\Risk\DepositPolicy;
use App\Domain\Risk\RiskScoreClient;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        protected CreateBooking $createBooking,
        protected RiskScoreClient $riskClient,
        protected DepositPolicy $depositPolicy
    ) {}

    /**
     * Render the public booking page.
     */
    public function index(?string $slug = null): Response
    {
        $businessQuery = Business::query()
            ->active()
            ->with([
                'businessType',
                'locations.employees',
                'services.locations',
                'services.employees',
            ]);

        $business = $slug
            ? $businessQuery->where('slug', $slug)->firstOrFail()
            : $businessQuery->firstOrFail();

        // Unique employees working across all locations
        $employees = $business->locations
            ->flatMap(fn ($loc) => $loc->employees)
            ->unique('id')
            ->values()
            ->map(fn ($emp) => [
                'id' => $emp->id,
                'name' => $emp->name,
                'email' => $emp->email,
                'avatar_path' => $emp->avatar_path,
                'role' => 'Specialist',
            ]);

        return Inertia::render('booking/index', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'about' => $business->about,
                'city' => $business->city,
                'country' => $business->country,
                'timezone' => $business->timezone,
                'phone' => $business->phone,
                'cancellation_notice' => $business->cancellation_notice ?? 'Free cancellation up to 24h prior.',
                'locations' => $business->locations->map(fn (Location $loc) => [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'address' => $loc->address,
                    'city' => $loc->city,
                    'is_active' => $loc->is_active,
                    'max_capacity' => $loc->max_capacity,
                    'opens_at' => $loc->opens_at ? substr((string) $loc->opens_at, 0, 5) : null,
                    'closes_at' => $loc->closes_at ? substr((string) $loc->closes_at, 0, 5) : null,
                    'employee_ids' => $loc->employees->pluck('id'),
                ]),
                'services' => $business->services->where('is_active', true)->values()->map(fn ($srv) => [
                    'id' => $srv->id,
                    'name' => $srv->name,
                    'description' => $srv->description,
                    'price' => (float) $srv->price,
                    'duration_minutes' => $srv->duration_minutes,
                    'booking_fee' => (float) $srv->booking_fee,
                    'category' => $srv->category,
                    'is_recommended' => $srv->is_recommended,
                    'employee_ids' => $srv->employees->pluck('id'),
                ]),
                'employees' => $employees,
            ],
        ]);
    }

    /**
     * Calculate dynamically available slots considering employee schedules,
     * location opening hours, closures, seat capacity and 10-minute buffers.
     */
    public function availableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'service_id' => 'required|exists:services,id',
            'location_id' => 'required|exists:locations,id',
            'employee_user_id' => 'nullable|exists:users,id',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $service = Service::with('serviceLocations')->whereKey($validated['service_id'])->firstOrFail();
        $location = Location::query()->whereKey($validated['location_id'])->firstOrFail();
        $duration = $service->durationFor($location);
        $bufferMinutes = 10;

        $targetDate = Carbon::parse($validated['date']);

        // Location hours
        if (! $location->is_active) {
            return $this->closedDayResponse($targetDate, 'This location is not accepting bookings.');
        }

        $window = [
            'opens' => $location->opens_at ? substr((string) $location->opens_at, 0, 5) : '09:00',
            'closes' => $location->closes_at ? substr((string) $location->closes_at, 0, 5) : '19:00',
        ];

        $openTime = Carbon::parse($targetDate->toDateString().' '.$window['opens']);
        $closeTime = Carbon::parse($targetDate->toDateString().' '.$window['closes']);

        // Fetch all occupying bookings for this day (all employees)
        $existingBookings = Booking::query()
            ->where('business_id', $validated['business_id'])
            ->where('location_id', $validated['location_id'])
            ->occupying()
            ->whereDate('start_at', $targetDate->toDateString())
            ->get(['id', 'start_at', 'end_at', 'buffer_minutes', 'employee_user_id']);

        $capacity = (int) $location->max_capacity;
        $selectedEmployeeId = ! empty($validated['employee_user_id']) ? (int) $validated['employee_user_id'] : null;

        $slots = [];
        $current = $openTime->copy();

        while ($current->copy()->addMinutes($duration)->lte($closeTime)) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addMinutes($duration);
            $slotOccupancyEnd = $slotEnd->copy()->addMinutes($bufferMinutes);

            // Skip past slots for today
            if ($slotStart->isPast()) {
                $current->addMinutes(30);

                continue;
            }

            $seatsTaken = 0;
            $employeeBusy = false;

            foreach ($existingBookings as $booking) {
                $bookingStart = Carbon::parse($booking->start_at);
                $bookingOccupancyEnd = Carbon::parse($booking->end_at)->addMinutes($booking->buffer_minutes ?? $bufferMinutes);

                $overlaps = $slotStart->lt($bookingOccupancyEnd) && $slotOccupancyEnd->gt($bookingStart);

                if (! $overlaps) {
                    continue;
                }

                $seatsTaken++;

                if ($selectedEmployeeId !== null && $booking->employee_user_id === $selectedEmployeeId) {
                    $employeeBusy = true;
                }
            }

            if ($seatsTaken < $capacity && ! $employeeBusy) {
                $slots[] = [
                    'time' => $slotStart->format('H:i'),
                    'start_at' => $slotStart->toIso8601String(),
                    'end_at' => $slotEnd->toIso8601String(),
                    'label' => $slotStart->format('g:i A').' - '.$slotEnd->format('g:i A'),
                    'seats_left' => max(0, $capacity - $seatsTaken),
                ];
            }

            // Move forward by 30-min increments
            $current->addMinutes(30);
        }

        return response()->json([
            'date' => $targetDate->toDateString(),
            'service_duration' => $duration,
            'buffer_minutes' => $bufferMinutes,
            'capacity' => $capacity,
            'location_closed' => false,
            'closed_reason' => null,
            'opens_at' => $window['opens'],
            'closes_at' => $window['closes'],
            'available_slots' => $slots,
            'is_fully_booked' => count($slots) === 0,
        ]);
    }

    /**
     * JSON payload for a closed/inactive location day.
     */
    private function closedDayResponse(Carbon $targetDate, string $reason): JsonResponse
    {
        return response()->json([
            'date' => $targetDate->toDateString(),
            'location_closed' => true,
            'closed_reason' => $reason,
            'available_slots' => [],
            'is_fully_booked' => true,
        ]);
    }

    /**
     * Preview attendance risk score and deposit requirement.
     */
    public function riskPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'start_at' => 'required|date',
        ]);

        $service = Service::query()->whereKey($validated['service_id'])->firstOrFail();
        $client = $request->user();
        $startAt = Carbon::parse($validated['start_at']);
        $leadHours = max(1, now()->diffInHours($startAt, false));

        // Predict via risk service client
        $assessment = $this->riskClient->calculateRiskScore([
            'lead_time_hours' => $leadHours,
            'service_price' => (float) $service->price,
            'historical_no_show_rate' => $client ? $this->calculateUserNoShowRate($client) : 0.0,
            'day_of_week' => $startAt->dayOfWeekIso,
            'hour_of_day' => $startAt->hour,
            'client_total_bookings' => $client ? $client->bookings()->count() : 0,
            'client_cancelled_bookings' => $client ? $client->bookings()->where('status', 'cancelled')->count() : 0,
            'is_weekend' => (int) $startAt->isWeekend(),
            'is_first_time_client' => $client && $client->bookings()->count() === 0 ? 1 : 0,
        ]);

        $policy = $this->depositPolicy->evaluate($service, $client, $assessment);

        return response()->json([
            'risk_score' => $assessment['risk_score'],
            'risk_tier' => $assessment['risk_tier'],
            'requires_deposit' => $policy['deposit_required'],
            'deposit_amount' => $policy['amount'],
            'reason' => $policy['reason'],
        ]);
    }

    /**
     * Store new booking with risk assessment and deposit enforcement.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'location_id' => 'required|exists:locations,id',
            'service_id' => 'required|exists:services,id',
            'employee_user_id' => 'nullable|exists:users,id',
            'start_at' => 'required|date|after:now',
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:30',
            'client_note' => 'nullable|string|max:1000',
            'deposit_confirmed' => 'nullable|boolean',
            'deposit_amount' => 'nullable|numeric',
        ]);

        $service = Service::query()->whereKey($validated['service_id'])->firstOrFail();
        $startAt = Carbon::parse($validated['start_at']);

        // Public PWA flow: identify the client by email, creating the account on first visit.
        $client = User::query()->where('email', $validated['client_email'])->first()
            ?? User::query()->create([
                'name' => $validated['client_name'],
                'email' => $validated['client_email'],
                'password' => Hash::make('password'),
            ]);

        if (! empty($validated['client_phone']) && $client->phone !== $validated['client_phone']) {
            $client->update(['phone' => $validated['client_phone']]);
        }

        // Assess attendance risk
        $leadHours = max(1, now()->diffInHours($startAt, false));
        $riskAssessment = $this->riskClient->calculateRiskScore([
            'lead_time_hours' => $leadHours,
            'service_price' => (float) $service->price,
            'historical_no_show_rate' => $this->calculateUserNoShowRate($client),
            'day_of_week' => $startAt->dayOfWeekIso,
            'hour_of_day' => $startAt->hour,
            'client_total_bookings' => $client->bookings()->count(),
            'client_cancelled_bookings' => $client->bookings()->where('status', 'cancelled')->count(),
            'is_weekend' => (int) $startAt->isWeekend(),
            'is_first_time_client' => $client->bookings()->count() === 0 ? 1 : 0,
        ]);

        $policy = $this->depositPolicy->evaluate($service, $client, $riskAssessment);

        // Enforce deposit if required
        $isDepositConfirmed = $request->boolean('deposit_confirmed');
        if ($policy['deposit_required'] && ! $isDepositConfirmed) {
            return response()->json([
                'status' => 'deposit_required',
                'deposit_amount' => $policy['amount'],
                'risk_score' => $riskAssessment['risk_score'],
                'risk_tier' => $riskAssessment['risk_tier'],
                'reason' => $policy['reason'],
                'message' => 'A refundable deposit is required to hold this high-demand slot.',
            ], 422);
        }

        $depositStatus = $policy['deposit_required'] ? 'paid' : 'none';

        try {
            $booking = $this->createBooking->execute(new BookingData(
                businessId: (int) $validated['business_id'],
                locationId: (int) $validated['location_id'],
                serviceId: (int) $validated['service_id'],
                clientUserId: $client->id,
                employeeUserId: ! empty($validated['employee_user_id']) ? (int) $validated['employee_user_id'] : null,
                startAt: $startAt,
                clientNote: $validated['client_note'] ?? null,
                depositAmount: $policy['deposit_required'] ? $policy['amount'] : 0.0,
                depositStatus: $depositStatus,
                riskScore: $riskAssessment['risk_score'],
                riskTier: $riskAssessment['risk_tier'],
            ));

            return response()->json([
                'status' => 'success',
                'reference_code' => $booking->reference_code,
                'booking_id' => $booking->id,
                'redirect_url' => route('booking.confirmation', ['reference_code' => $booking->reference_code]),
            ], 201);
        } catch (SlotAlreadyBookedException $e) {
            return response()->json([
                'status' => 'conflict',
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Show booking confirmation page with calendar and WhatsApp reminder summary.
     */
    public function confirmation(string $reference_code): Response
    {
        $booking = Booking::query()
            ->with(['business', 'service', 'location', 'employee', 'reminders'])
            ->where('reference_code', $reference_code)
            ->firstOrFail();

        return Inertia::render('booking/confirmation', [
            'booking' => [
                'id' => $booking->id,
                'reference_code' => $booking->reference_code,
                'status' => $booking->status->value,
                'start_at' => $booking->start_at->toIso8601String(),
                'end_at' => $booking->end_at->toIso8601String(),
                'duration_minutes' => $booking->duration_minutes,
                'total_amount' => (float) $booking->total_amount,
                'deposit_amount' => (float) $booking->deposit_amount,
                'deposit_status' => $booking->deposit_status,
                'client_note' => $booking->client_note,
                'business' => [
                    'name' => $booking->business->name,
                    'phone' => $booking->business->phone,
                    'timezone' => $booking->business->timezone,
                    'cancellation_notice' => $booking->business->cancellation_notice,
                ],
                'service' => [
                    'name' => $booking->service->name,
                    'price' => (float) $booking->service->price,
                ],
                'location' => [
                    'name' => $booking->location->name,
                    'address' => $booking->location->address,
                ],
                'employee' => $booking->employee ? [
                    'name' => $booking->employee->name,
                ] : null,
                'reminders' => $booking->reminders->map(fn ($r) => [
                    'type' => $r->type,
                    'channel' => $r->channel,
                    'scheduled_for' => $r->scheduled_for->toIso8601String(),
                    'delivery_status' => $r->delivery_status,
                ]),
            ],
        ]);
    }

    protected function calculateUserNoShowRate(User $user): float
    {
        $total = $user->bookings()->count();
        if ($total === 0) {
            return 0.0;
        }

        $noShows = $user->bookings()->where('status', 'no_show')->count();

        return round($noShows / $total, 4);
    }
}
