<?php

namespace App\Domain\Reminders\Channels;

use App\Models\Reminder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel implements ReminderChannelInterface
{
    public function supports(string $channel): bool
    {
        return $channel === 'sms';
    }

    public function sendReminder(Reminder $reminder): array
    {
        $booking = $reminder->booking;
        $client = $booking->client;
        $phone = $client->phone ?? '+351910000000';

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        // Test or unconfigured simulation fallback
        if (empty($sid) || empty($token) || empty($from)) {
            $simulatedSid = 'SM'.bin2hex(random_bytes(16));
            Log::info("Twilio SMS simulation for booking {$booking->reference_code} to {$phone}");

            return [
                'success' => true,
                'provider_message_id' => $simulatedSid,
                'error' => null,
            ];
        }

        $formattedDate = $booking->start_at->format('M j \a\t g:i A');
        $body = "SlotSaver: {$booking->service->name} at {$booking->business->name} on {$formattedDate}. Reply C to confirm or manage your booking: ".url("/booking/manage/{$booking->reference_code}");

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->timeout(10)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'To' => $phone,
                    'From' => $from,
                    'Body' => $body,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'provider_message_id' => $data['sid'] ?? null,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'provider_message_id' => null,
                'error' => $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'provider_message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
