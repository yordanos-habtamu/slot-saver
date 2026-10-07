<?php

namespace App\Console\Commands;

use App\Domain\Reminders\Jobs\SendReminderJob;
use App\Models\Reminder;
use Illuminate\Console\Command;

class DispatchUpcomingRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch pending reminders scheduled to be sent';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dueReminders = Reminder::query()
            ->due()
            ->with(['booking.client', 'booking.service', 'booking.business'])
            ->limit(100)
            ->get();

        $count = $dueReminders->count();

        foreach ($dueReminders as $reminder) {
            SendReminderJob::dispatch($reminder);
        }

        $this->info("Dispatched {$count} reminder job(s).");

        return self::SUCCESS;
    }
}
