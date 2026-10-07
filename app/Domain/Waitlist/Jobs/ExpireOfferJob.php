<?php

namespace App\Domain\Waitlist\Jobs;

use App\Domain\Waitlist\Actions\OfferFreedSlot;
use App\Models\WaitlistEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireOfferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public WaitlistEntry $entry
    ) {}

    public function handle(OfferFreedSlot $offerFreedSlot): void
    {
        $this->entry->refresh();

        // If user already claimed or cancelled, or offer has not yet expired, abort
        if ($this->entry->status !== 'offered') {
            return;
        }

        if ($this->entry->offer_expires_at !== null && $this->entry->offer_expires_at->isFuture()) {
            return;
        }

        // Expire this candidate's hold
        $this->entry->update(['status' => 'expired']);
        Log::info("Waitlist offer for entry #{$this->entry->id} expired. Cascading to next candidate.");

        // Cascade offer to the next waitlisted customer
        if ($this->entry->freedBooking) {
            $offerFreedSlot->execute($this->entry->freedBooking);
        }
    }
}
