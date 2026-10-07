<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     * Supports both Client and Business Owner registrations with auto-verification.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => ['nullable', 'string', 'in:client,owner'],
            'phone' => ['nullable', 'string', 'max:30'],
            'business_name' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $role = ($input['role'] ?? 'client') === 'owner' ? UserRole::Owner : UserRole::Client;

        $user = new User([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'phone' => $input['phone'] ?? null,
            'role' => $role,
        ]);

        // Auto-verify email so user is never blocked by verification prompts
        $user->email_verified_at = now();
        $user->save();

        // If registered as a business owner, auto-scaffold their business
        if ($role === UserRole::Owner) {
            $businessName = ! empty($input['business_name'])
                ? trim($input['business_name'])
                : $user->name.' Studio';

            $businessType = BusinessType::first() ?? BusinessType::create([
                'slug' => 'general-services',
                'name' => 'General Services',
                'description' => 'Appointment based services',
            ]);

            $business = Business::create([
                'business_type_id' => $businessType->id,
                'owner_user_id' => $user->id,
                'name' => $businessName,
                'slug' => Str::slug($businessName).'-'.Str::lower(Str::random(5)),
                'email' => $user->email,
                'phone' => $input['phone'] ?? null,
                'address_line1' => 'Main Avenue 10',
                'city' => 'Lisbon',
                'country' => 'Portugal',
                'timezone' => 'Europe/Lisbon',
                'status' => BusinessStatus::Active,
                'cancellation_notice' => 'Free cancellation up to 24 hours prior to appointment.',
            ]);

            Location::create([
                'business_id' => $business->id,
                'name' => 'Main Location',
                'address_line1' => 'Main Avenue 10',
                'city' => 'Lisbon',
                'country' => 'Portugal',
                'timezone' => 'Europe/Lisbon',
                'max_capacity' => 4,
            ]);
        }

        return $user;
    }
}
