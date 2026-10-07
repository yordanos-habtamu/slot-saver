<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    /**
     * Locations are managed by the owner of their parent business.
     * Platform admins pass through Gate::before.
     */
    public function manage(User $user, Location $location): bool
    {
        return $location->business->owner_user_id === $user->id;
    }
}
