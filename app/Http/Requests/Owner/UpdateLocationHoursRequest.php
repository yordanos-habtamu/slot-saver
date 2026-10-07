<?php

namespace App\Http\Requests\Owner;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::Owner, UserRole::Admin], true);
    }

    /**
     * A full week: exactly seven entries, ISO days 1–7, each used once.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
            'hours.*.is_closed' => ['required', 'boolean'],
            'hours.*.opens_at' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'hours.*.closes_at' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('hours', []) as $index => $day) {
                if (! is_array($day)) {
                    continue;
                }

                if (($day['is_closed'] ?? false) === true || ($day['is_closed'] ?? false) === 1) {
                    continue;
                }

                $opens = $day['opens_at'] ?? null;
                $closes = $day['closes_at'] ?? null;

                if ($opens === null || $closes === null) {
                    $validator->errors()->add(
                        "hours.{$index}.opens_at",
                        'Open and close times are required for every day that is not closed.'
                    );

                    continue;
                }

                if (strtotime((string) $opens) >= strtotime((string) $closes)) {
                    $validator->errors()->add(
                        "hours.{$index}.closes_at",
                        'The closing time must be later than the opening time.'
                    );
                }
            }
        });
    }
}
