<?php

namespace App\Http\Requests\Admin;

use App\Concerns\PasswordValidationRules;
use App\Models\BusinessType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreBusinessRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business.business_type_id' => [
                'required',
                'integer',
                Rule::exists(BusinessType::class, 'id')->where('is_active', true),
            ],
            'business.name' => ['required', 'string', 'max:255'],
            'business.email' => ['required', 'email', 'max:255'],
            'business.phone' => ['nullable', 'string', 'max:40'],
            'business.about' => ['nullable', 'string', 'max:2000'],
            'business.website' => ['nullable', 'url', 'max:255'],
            'business.address_line1' => ['required', 'string', 'max:255'],
            'business.address_line2' => ['nullable', 'string', 'max:255'],
            'business.city' => ['required', 'string', 'max:120'],
            'business.state' => ['nullable', 'string', 'max:120'],
            'business.country' => ['required', 'string', 'max:120'],
            'business.postal_code' => ['nullable', 'string', 'max:20'],
            'business.timezone' => ['required', 'string', 'timezone', 'max:64'],
            'owner.name' => ['required', 'string', 'max:255'],
            'owner.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner.password' => $this->passwordRules(),
            'location.name' => ['nullable', 'string', 'max:255'],
            'location.address_line1' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The validated payload in the shape the registration action expects.
     *
     * @return array{
     *     business_type_id: int,
     *     business: array<string, mixed>,
     *     owner: array<string, mixed>,
     *     location: array{name: string|null, address_line1: string|null},
     * }
     */
    public function payload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return [
            'business_type_id' => $validated['business']['business_type_id'],
            'business' => Arr::except($validated['business'], 'business_type_id'),
            'owner' => $validated['owner'],
            'location' => [
                'name' => $validated['location']['name'] ?? null,
                'address_line1' => $validated['location']['address_line1'] ?? null,
            ],
        ];
    }
}
