<?php

namespace App\Domain\Waitlist\Listeners;

use App\Domain\Booking\Events\BookingCancelledEvent;
use App\Domain\Waitlist\Actions\OfferFreedSlot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TriggerWaitlistRefillOnCancellation implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected OfferFreedSlot $offerFreedSlot
    ) {}

    public function handle(BookingCancelledEvent $event): void
    {
        $this->offerFreedSlot->execute($event->booking);
    }
}
