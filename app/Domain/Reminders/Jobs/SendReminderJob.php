<?php

namespace App\Domain\Reminders\Jobs;

use App\Domain\Reminders\Channels\SmsChannel;
use App\Domain\Reminders\Channels\WhatsAppChannel;
use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Exponential backoff in seconds: 1 min, 5 mins, 15 mins.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(
        public Reminder $reminder
    ) {}

    public function handle(WhatsAppChannel $whatsApp, SmsChannel $sms): void
    {
        $this->reminder->refresh();

        // If reminder was already sent or cancelled in the meantime, abort
        if ($this->reminder->delivery_status !== 'scheduled') {
            return;
        }

        // Try primary channel (WhatsApp)
        $result = $whatsApp->sendReminder($this->reminder);

        if ($result['success']) {
            $this->reminder->update([
                'channel' => 'whatsapp',
                'delivery_status' => 'sent',
                'sent_at' => now(),
                'provider_message_id' => $result['provider_message_id'],
                'error_details' => null,
            ]);

            return;
        }

        // WhatsApp failed: record attempt
        $attempts = $this->attempts();
        $this->reminder->increment('retry_count');
        $this->reminder->update([
            'error_details' => "WhatsApp attempt {$attempts} failed: {$result['error']}",
        ]);

        // If this is the final attempt, attempt SMS fallback
        if ($attempts >= $this->tries) {
            Log::warning("WhatsApp failed 3 times for reminder #{$this->reminder->id}. Triggering SMS fallback.");
            $smsResult = $sms->sendReminder($this->reminder);

            if ($smsResult['success']) {
                $this->reminder->update([
                    'channel' => 'sms',
                    'delivery_status' => 'sent',
                    'sent_at' => now(),
                    'provider_message_id' => $smsResult['provider_message_id'],
                    'error_details' => 'Delivered via SMS fallback after WhatsApp outages.',
                ]);

                return;
            }

            // Both failed
            $this->reminder->update([
                'delivery_status' => 'failed',
                'error_details' => "WhatsApp and SMS failed: {$smsResult['error']}",
            ]);

            Log::error("CRITICAL: Outage delivering reminder #{$this->reminder->id} for booking {$this->reminder->booking->reference_code}");

            return;
        }

        // Throw exception to trigger queue backoff retry
        throw new \RuntimeException("WhatsApp reminder delivery failed: {$result['error']}");
    }
}
