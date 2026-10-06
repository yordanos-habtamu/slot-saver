<?php

namespace App\Actions\Admin;

use App\Enums\UserRole;
use App\Models\User;

class CreateUserAccount
{
    /**
     * Create an account for somebody the platform admin already vouches for.
     *
     * The role is deliberately not mass assignable, so it is always set here.
     *
     * @param  array{name: string, email: string, password: string, phone?: string|null, timezone?: string|null, address?: string|null}  $attributes
     */
    public function handle(array $attributes, UserRole $role, bool $emailVerified = false): User
    {
        $user = new User([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
            'phone' => $attributes['phone'] ?? null,
            'timezone' => $attributes['timezone'] ?? null,
            'address' => $attributes['address'] ?? null,
        ]);

        $user->role = $role;
        $user->email_verified_at = $emailVerified ? now() : null;
        $user->save();

        return $user;
    }
}
