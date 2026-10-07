<?php

namespace App\Domain\Reminders\Channels;

use App\Models\Reminder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel implements ReminderChannelInterface
{
    public function supports(string $channel): bool
    {
        return $channel === 'whatsapp';
    }

    public function sendReminder(Reminder $reminder): array
    {
        $booking = $reminder->booking;
        $client = $booking->client;
        $phone = $client->phone ?? '+351910000000';

        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        // Test or unconfigured fallback
        if (empty($token) || empty($phoneNumberId)) {
            $simulatedMessageId = 'wamid.simulated_'.bin2hex(random_bytes(8));
            Log::info("WhatsApp simulation for booking {$booking->reference_code} to {$phone}");

            return [
                'success' => true,
                'provider_message_id' => $simulatedMessageId,
                'error' => null,
            ];
        }

        $formattedDate = $booking->start_at->format('M j, Y \a\t g:i A');
        $bodyText = "Hi {$client->name}! Reminder for your {$booking->service->name} at {$booking->business->name} on {$formattedDate}. Please let us know if you will make it:";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => [
                    'text' => $bodyText,
                ],
                'action' => [
                    'buttons' => [
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => "confirm_{$booking->reference_code}",
                                'title' => 'Confirm ✅',
                            ],
                        ],
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => "reschedule_{$booking->reference_code}",
                                'title' => 'Reschedule 🔄',
                            ],
                        ],
                        [
                            'type' => 'reply',
                            'reply' => [
                                'id' => "cancel_{$booking->reference_code}",
                                'title' => 'Cancel ❌',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post("https://graph.facebook.com/v20.0/{$phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $wamid = $data['messages'][0]['id'] ?? null;

                return [
                    'success' => true,
                    'provider_message_id' => $wamid,
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
