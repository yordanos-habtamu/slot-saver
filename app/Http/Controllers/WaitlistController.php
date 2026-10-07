<?php

namespace App\Http\Controllers;

use App\Domain\Waitlist\Actions\ClaimFreedSlot;
use App\Domain\Waitlist\Actions\JoinWaitlist;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WaitlistController extends Controller
{
    public function __construct(
        protected JoinWaitlist $joinWaitlist,
        protected ClaimFreedSlot $claimFreedSlot
    ) {}

    /**
     * Authenticated API endpoint to join the waitlist.
     */
    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'service_id' => 'required|exists:services,id',
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time_from' => 'nullable|date_format:H:i',
            'preferred_time_to' => 'nullable|date_format:H:i',
            'preferred_employee_id' => 'nullable|exists:users,id',
        ]);

        $entry = $this->joinWaitlist->execute(
            businessId: (int) $validated['business_id'],
            clientUserId: $request->user()->id,
            serviceId: (int) $validated['service_id'],
            preferredDate: Carbon::parse($validated['preferred_date']),
            timeFrom: $validated['preferred_time_from'] ?? null,
            timeTo: $validated['preferred_time_to'] ?? null,
            preferredEmployeeId: isset($validated['preferred_employee_id']) ? (int) $validated['preferred_employee_id'] : null,
        );

        return response()->json([
            'status' => 'success',
            'message' => 'You have been added to the waitlist. We will notify you if a slot opens up.',
            'entry' => $entry,
        ], 201);
    }

    /**
     * Inspect waitlist offer status and remaining countdown seconds.
     */
    public function showOffer(Request $request, string $token): \Illuminate\Http\Response|JsonResponse|Response
    {
        $entry = WaitlistEntry::query()
            ->with(['service', 'business', 'freedBooking.location', 'freedBooking.employee'])
            ->where('claim_token', $token)
            ->first();

        if (! $entry) {
            if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                return response()->json(['status' => 'not_found', 'message' => 'Offer token invalid.'], 404);
            }
            abort(404, 'Waitlist offer token not found or already consumed.');
        }

        $remainingSeconds = $entry->offer_expires_at
            ? max(0, now()->diffInSeconds($entry->offer_expires_at, false))
            : 0;

        $offerData = [
            'token' => $token,
            'status' => $entry->status,
            'is_claimable' => $entry->isClaimable(),
            'remaining_seconds' => $remainingSeconds,
            'expires_at' => $entry->offer_expires_at?->toIso8601String(),
            'service' => [
                'name' => $entry->service->name,
                'price' => (float) $entry->service->price,
                'duration' => $entry->service->duration_minutes,
            ],
            'business' => [
                'name' => $entry->business->name,
                'phone' => $entry->business->phone,
            ],
            'slot' => $entry->freedBooking ? [
                'start_at' => $entry->freedBooking->start_at->toIso8601String(),
                'end_at' => $entry->freedBooking->end_at->toIso8601String(),
                'location_name' => $entry->freedBooking->location->name,
                'employee_name' => $entry->freedBooking->employee?->name,
            ] : null,
        ];

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($offerData);
        }

        return Inertia::render('waitlist/claim', ['offer' => $offerData]);
    }

    /**
     * Claim the offered slot.
     */
    public function claim(string $token): JsonResponse
    {
        try {
            $result = $this->claimFreedSlot->execute($token);

            return response()->json([
                'status' => 'success',
                'message' => 'Slot claimed successfully! Your appointment is confirmed.',
                'booking' => [
                    'id' => $result['booking']->id,
                    'reference_code' => $result['booking']->reference_code,
                    'start_at' => $result['booking']->start_at->toIso8601String(),
                    'end_at' => $result['booking']->end_at->toIso8601String(),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
