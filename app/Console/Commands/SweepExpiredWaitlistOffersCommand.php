<?php

namespace App\Console\Commands;

use App\Domain\Waitlist\Actions\OfferFreedSlot;
use App\Models\WaitlistEntry;
use Illuminate\Console\Command;

class SweepExpiredWaitlistOffersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waitlist:sweep-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sweep expired waitlist offers and cascade to next candidates';

    /**
     * Execute the console command.
     */
    public function handle(OfferFreedSlot $offerFreedSlot): int
    {
        $expiredEntries = WaitlistEntry::query()
            ->expiredOffers()
            ->with('freedBooking')
            ->get();

        $count = $expiredEntries->count();

        foreach ($expiredEntries as $entry) {
            $entry->update(['status' => 'expired']);

            if ($entry->freedBooking) {
                $offerFreedSlot->execute($entry->freedBooking);
            }
        }

        $this->info("Swept and cascaded {$count} expired waitlist offer(s).");

        return self::SUCCESS;
    }
}
