<?php

namespace App\Domain\Waitlist\Actions;

use App\Domain\Waitlist\Jobs\ExpireOfferJob;
use App\Models\Booking;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\Log;

class OfferFreedSlot
{
    /**
     * Offer a cancelled appointment slot to the next candidate on the waitlist.
     */
    public function execute(Booking $freedBooking): ?WaitlistEntry
    {
        $candidate = WaitlistEntry::query()
            ->waiting()
            ->where('business_id', $freedBooking->business_id)
            ->where('service_id', $freedBooking->service_id)
            ->whereDate('preferred_date', $freedBooking->start_at->toDateString())
            ->where(function ($query) use ($freedBooking) {
                $query->whereNull('preferred_employee_id')
                    ->orWhere('preferred_employee_id', $freedBooking->employee_user_id);
            })
            ->orderBy('created_at', 'asc')
            ->first();

        if (! $candidate) {
            Log::info("No eligible waitlist candidate found for freed booking {$freedBooking->reference_code}");

            return null;
        }

        $token = WaitlistEntry::generateClaimToken();
        $expiresAt = now()->addMinutes(15);

        $candidate->update([
            'status' => 'offered',
            'claim_token' => $token,
            'offered_at' => now(),
            'offer_expires_at' => $expiresAt,
            'freed_booking_id' => $freedBooking->id,
        ]);

        Log::info("Offered freed slot {$freedBooking->reference_code} to waitlist candidate #{$candidate->id} with token {$token}. Expires at {$expiresAt}");

        // Dispatch 15-minute countdown expiration job
        ExpireOfferJob::dispatch($candidate)->delay($expiresAt);

        return $candidate;
    }
}
