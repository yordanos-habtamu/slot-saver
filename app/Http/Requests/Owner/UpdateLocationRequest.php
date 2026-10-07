<?php

namespace App\Http\Requests\Owner;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::Owner, UserRole::Admin], true);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'address_line1' => ['sometimes', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['sometimes', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'timezone' => ['sometimes', 'string', 'timezone', 'max:64'],
            'phone' => ['nullable', 'string', 'max:40'],
            'max_capacity' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'max_bookings_per_day' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
