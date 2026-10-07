<?php

namespace Tests\Feature;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Data\BookingData;
use App\Domain\Booking\Events\BookingCancelledEvent;
use App\Domain\Reminders\Jobs\SendReminderJob;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Location;
use App\Models\Reminder;
use App\Models\Service;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReminderPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_scheduler_command_dispatches_due_reminders(): void
    {
        Queue::fake([SendReminderJob::class]);

        $booking = Booking::factory()->create();

        // 1 past due reminder
        $dueReminder = Reminder::create([
            'booking_id' => $booking->id,
            'channel' => 'whatsapp',
            'type' => '24h',
            'scheduled_for' => now()->subMinute(),
            'delivery_status' => 'scheduled',
        ]);

        // 1 future reminder
        Reminder::create([
            'booking_id' => $booking->id,
            'channel' => 'whatsapp',
            'type' => '2h',
            'scheduled_for' => now()->addHour(),
            'delivery_status' => 'scheduled',
        ]);

        $this->artisan('reminders:dispatch')
            ->assertExitCode(0);

        Queue::assertPushed(SendReminderJob::class, function ($job) use ($dueReminder) {
            return $job->reminder->id === $dueReminder->id;
        });

        Queue::assertPushed(SendReminderJob::class, 1);
    }

    public function test_send_reminder_job_executes_and_marks_reminder_sent(): void
    {
        $business = Business::factory()->create();
        $location = Location::factory()->create(['business_id' => $business->id]);
        $service = Service::factory()->create(['business_id' => $business->id]);
        $client = User::factory()->client()->create(['phone' => '+351912345678']);
        $employee = User::factory()->employee()->create();

        $booking = (new CreateBooking)->execute(new BookingData(
            businessId: $business->id,
            locationId: $location->id,
            serviceId: $service->id,
            clientUserId: $client->id,
            employeeUserId: $employee->id,
            startAt: Carbon::now()->addDays(2),
        ));

        $reminder = $booking->reminders->first();
        $this->assertSame('scheduled', $reminder->delivery_status);

        $job = new SendReminderJob($reminder);
        app()->call([$job, 'handle']);

        $reminder->refresh();
        $this->assertSame('sent', $reminder->delivery_status);
        $this->assertNotNull($reminder->sent_at);
        $this->assertNotNull($reminder->provider_message_id);
    }

    public function test_whatsapp_webhook_verification_handshake(): void
    {
        config(['services.whatsapp.verify_token' => 'test_token_123']);

        $response = $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=test_token_123&hub_challenge=CHALLENGE_ACCEPTED');

        $response->assertStatus(200);
        $this->assertSame('CHALLENGE_ACCEPTED', $response->getContent());

        // Invalid token returns 403
        $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong_token&hub_challenge=TEST')
            ->assertStatus(403);
    }

    public function test_inbound_whatsapp_webhook_confirms_booking(): void
    {
        $booking = Booking::factory()->create([
            'reference_code' => 'SS-CONFIRM1',
            'status' => BookingStatus::Pending,
        ]);

        $reminder = Reminder::create([
            'booking_id' => $booking->id,
            'channel' => 'whatsapp',
            'type' => '24h',
            'scheduled_for' => now()->subHour(),
            'delivery_status' => 'sent',
            'provider_message_id' => 'wamid.HBgM001',
        ]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.HBgM001_reply',
                                        'from' => '351912345678',
                                        'interactive' => [
                                            'button_reply' => [
                                                'id' => 'confirm_SS-CONFIRM1',
                                                'title' => 'Confirm ✅',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhooks/whatsapp', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'button_processed')
            ->assertJsonPath('result.action', 'confirm')
            ->assertJsonPath('result.success', true);

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertNotNull($booking->confirmed_at);

        $reminder->refresh();
        $this->assertSame('confirm', $reminder->interactive_action);
    }

    public function test_inbound_whatsapp_webhook_cancels_booking_and_fires_waitlist_event(): void
    {
        Event::fake([BookingCancelledEvent::class]);

        $booking = Booking::factory()->create([
            'reference_code' => 'SS-CANCEL01',
            'status' => BookingStatus::Confirmed,
        ]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.HBgM002_cancel',
                                        'from' => '351912345678',
                                        'interactive' => [
                                            'button_reply' => [
                                                'id' => 'cancel_SS-CANCEL01',
                                                'title' => 'Cancel ❌',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhooks/whatsapp', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'button_processed')
            ->assertJsonPath('result.action', 'cancel');

        $booking->refresh();
        $this->assertSame(BookingStatus::Cancelled, $booking->status);

        Event::assertDispatched(BookingCancelledEvent::class);
    }

    public function test_webhook_idempotency_ignores_duplicate_deliveries(): void
    {
        $booking = Booking::factory()->create([
            'reference_code' => 'SS-IDEMP01',
            'status' => BookingStatus::Pending,
        ]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.DUPLICATE_TEST_KEY',
                                        'from' => '351912345678',
                                        'interactive' => [
                                            'button_reply' => [
                                                'id' => 'confirm_SS-IDEMP01',
                                                'title' => 'Confirm ✅',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // 1st delivery
        $res1 = $this->postJson('/webhooks/whatsapp', $payload);
        $res1->assertStatus(200)->assertJsonPath('status', 'button_processed');

        $this->assertEquals(1, WebhookEvent::where('idempotency_key', 'whatsapp:wamid.DUPLICATE_TEST_KEY')->count());

        // 2nd delivery (duplicate)
        $res2 = $this->postJson('/webhooks/whatsapp', $payload);
        $res2->assertStatus(200)->assertJsonPath('status', 'ignored_duplicate');

        // 3rd delivery (duplicate)
        $res3 = $this->postJson('/webhooks/whatsapp', $payload);
        $res3->assertStatus(200)->assertJsonPath('status', 'ignored_duplicate');

        $this->assertEquals(1, WebhookEvent::where('idempotency_key', 'whatsapp:wamid.DUPLICATE_TEST_KEY')->count());
    }
}
