<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreLocationClosureRequest;
use App\Http\Requests\Owner\StoreLocationRequest;
use App\Http\Requests\Owner\UpdateLocationHoursRequest;
use App\Http\Requests\Owner\UpdateLocationRequest;
use App\Models\Location;
use App\Models\LocationClosure;
use App\Models\LocationOpeningHour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LocationController extends Controller
{
    /**
     * Every location of the owner's business with its schedule and staff.
     */
    public function index(Request $request): JsonResponse
    {
        $business = $request->user()->ownedBusinesses()->firstOrFail();

        $locations = $business->locations()
            ->with([
                'openingHours' => fn ($query) => $query->orderBy('day_of_week'),
                'closures' => fn ($query) => $query->orderBy('starts_on'),
                'employees:id,name,email,role,avatar_path',
            ])
            ->withCount('bookings')
            ->orderBy('name')
            ->get()
            ->map(fn (Location $location): array => $this->transform($location));

        return response()->json(['locations' => $locations]);
    }

    /**
     * Register a new location for the owner's business.
     */
    public function store(StoreLocationRequest $request): JsonResponse
    {
        $business = $request->user()->ownedBusinesses()->firstOrFail();

        $location = $business->locations()->create($request->validated());

        return response()->json([
            'message' => 'Location created.',
            'location' => $this->transform($location->load(['openingHours', 'closures', 'employees'])->loadCount('bookings')),
        ], 201);
    }

    /**
     * One location in full: address, weekly hours, closures, assigned staff.
     */
    public function show(Location $location): JsonResponse
    {
        Gate::authorize('manage', $location);

        return response()->json([
            'location' => $this->transform($location->load(['openingHours', 'closures', 'employees'])->loadCount('bookings')),
        ]);
    }

    /**
     * Update location details (address, capacity, active flag, ...).
     */
    public function update(UpdateLocationRequest $request, Location $location): JsonResponse
    {
        Gate::authorize('manage', $location);

        $location->update($request->validated());

        return response()->json([
            'message' => 'Location updated.',
            'location' => $this->transform($location->refresh()->load(['openingHours', 'closures', 'employees'])->loadCount('bookings')),
        ]);
    }

    /**
     * Delete a location that never took a booking; otherwise ask for deactivation.
     */
    public function destroy(Location $location): JsonResponse
    {
        Gate::authorize('manage', $location);

        if ($location->bookings()->exists()) {
            return response()->json([
                'message' => 'This location already has bookings. Set it inactive instead of deleting it.',
            ], 422);
        }

        $location->delete();

        return response()->json(['message' => 'Location deleted.']);
    }

    /**
     * Replace the full weekly opening-hours schedule (7 days, ISO weekdays).
     */
    public function updateHours(UpdateLocationHoursRequest $request, Location $location): JsonResponse
    {
        Gate::authorize('manage', $location);

        $validatedHours = $request->validated('hours');
        $hours = collect(is_array($validatedHours) ? $validatedHours : [])->map(fn (array $day): array => [
            'day_of_week' => (int) $day['day_of_week'],
            'opens_at' => $this->normalizeTime($day['opens_at'] ?? '09:00'),
            'closes_at' => $this->normalizeTime($day['closes_at'] ?? '18:00'),
            'is_closed' => (bool) ($day['is_closed'] ?? false),
        ]);

        DB::transaction(function () use ($location, $hours): void {
            $location->openingHours()->delete();

            foreach ($hours as $day) {
                $location->openingHours()->create($day);
            }
        });

        return response()->json([
            'message' => 'Opening hours updated.',
            'opening_hours' => $this->hoursCollection($location),
        ]);
    }

    /**
     * Block the location for a date or date range (holiday, vacation, ...).
     */
    public function storeClosure(StoreLocationClosureRequest $request, Location $location): JsonResponse
    {
        Gate::authorize('manage', $location);

        $closure = $location->closures()->create($request->validated());

        return response()->json([
            'message' => 'Closure added.',
            'closure' => $this->closurePayload($closure),
        ], 201);
    }

    /**
     * Remove a closure again (e.g. plans changed).
     */
    public function destroyClosure(Location $location, LocationClosure $closure): JsonResponse
    {
        Gate::authorize('manage', $location);

        abort_unless($closure->location_id === $location->id, 404);

        $closure->delete();

        return response()->json(['message' => 'Closure removed.']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'address_line1' => $location->address_line1,
            'address_line2' => $location->address_line2,
            'city' => $location->city,
            'state' => $location->state,
            'country' => $location->country,
            'postal_code' => $location->postal_code,
            'timezone' => $location->timezone,
            'phone' => $location->phone,
            'max_capacity' => $location->max_capacity,
            'max_bookings_per_day' => $location->max_bookings_per_day,
            'is_active' => (bool) $location->is_active,
            'opens_at' => $location->opens_at ? substr((string) $location->opens_at, 0, 5) : null,
            'closes_at' => $location->closes_at ? substr((string) $location->closes_at, 0, 5) : null,
            'bookings_count' => $location->bookings_count ?? null,
            'has_schedule_configured' => $location->hasScheduleConfigured(),
            'opening_hours' => $this->hoursCollection($location),
            'closures' => $location->closures
                ->sortBy('starts_on')
                ->values()
                ->map(fn (LocationClosure $closure): array => $this->closurePayload($closure)),
            'employees' => $location->employees
                ->values()
                ->map(fn ($employee): array => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'role' => $employee->role->value,
                    'avatar_path' => $employee->avatar_path,
                    'is_active' => (bool) ($employee->pivot->is_active ?? true),
                ]),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function hoursCollection(Location $location): array
    {
        return $location->openingHours
            ->sortBy('day_of_week')
            ->values()
            ->map(fn (LocationOpeningHour $hours): array => [
                'day_of_week' => $hours->day_of_week,
                'opens_at' => substr((string) $hours->opens_at, 0, 5),
                'closes_at' => substr((string) $hours->closes_at, 0, 5),
                'is_closed' => (bool) $hours->is_closed,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function closurePayload(LocationClosure $closure): array
    {
        return [
            'id' => $closure->id,
            'starts_on' => $closure->starts_on->toDateString(),
            'ends_on' => $closure->ends_on?->toDateString(),
            'reason' => $closure->reason,
        ];
    }

    protected function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
