<?php

namespace App\Domain\Reminders\Channels;

use App\Models\Reminder;

interface ReminderChannelInterface
{
    /**
     * Send an interactive appointment reminder.
     *
     * @return array{success: bool, provider_message_id: string|null, error: string|null}
     */
    public function sendReminder(Reminder $reminder): array;

    /**
     * Does this channel handle the given channel slug?
     */
    public function supports(string $channel): bool;
}
