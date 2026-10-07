<?php

namespace App\Http\Requests\Owner;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class AssignLocationEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::Owner, UserRole::Admin], true);
    }

    /**
     * Two modes: attach an existing user by id, or provision a new
     * employee account with email, name and an initial password.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'required_without:email', 'exists:users,id'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:user_id'],
            'name' => ['nullable', 'string', 'max:255', 'required_with:email'],
            'password' => ['nullable', 'string', 'min:8', 'max:100', 'required_with:email'],
        ];
    }
}
