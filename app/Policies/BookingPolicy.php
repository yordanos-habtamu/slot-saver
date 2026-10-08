<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * The client who made the booking, or the staff looking after it.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $booking->client_user_id === $user->id || $this->staff($user, $booking);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->staff($user, $booking);
    }

    /**
     * Day-of staff actions (check-in, no-show, WhatsApp reminder) may only be
     * performed by the business owner or assigned staff — never the platform
     * admin, who is a superuser but not an operator of any salon.
     */
    public function manageStatus(User $user, Booking $booking): bool
    {
        return ! $user->isAdmin() && $this->staff($user, $booking);
    }

    /**
     * A booking can only be cancelled before it has been dealt with.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        if ($booking->status->isFinal()) {
            return false;
        }

        return $booking->client_user_id === $user->id || $this->staff($user, $booking);
    }

    /**
     * Only the client may leave a review, and only once the visit happened.
     */
    public function review(User $user, Booking $booking): bool
    {
        return $booking->client_user_id === $user->id
            && $booking->status === BookingStatus::Completed
            && $booking->review()->doesntExist();
    }

    /**
     * The business owner, or an employee assigned to the booking or its location.
     */
    protected function staff(User $user, Booking $booking): bool
    {
        if ($booking->business->owner_user_id === $user->id) {
            return true;
        }

        if ($booking->employee_user_id === $user->id) {
            return true;
        }

        return $booking->location
            ->employees()
            ->whereKey($user->id)
            ->exists();
    }
}
