<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    /**
     * Only the platform admin works through the business register.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Anyone may look at a business that is open for business.
     */
    public function view(User $user, Business $business): bool
    {
        return $business->status->acceptsBookings() || $this->manage($user, $business);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Business $business): bool
    {
        return $this->manage($user, $business);
    }

    /**
     * Suspending or archiving is handled through the status, not a hard delete.
     */
    public function delete(User $user, Business $business): bool
    {
        return $user->isAdmin();
    }

    /**
     * The owner of the business and its listed employees manage it.
     */
    protected function manage(User $user, Business $business): bool
    {
        return $business->owner_user_id === $user->id
            || $business->locations()->whereHas('employees', fn ($query) => $query->whereKey($user->id))->exists();
    }
}
