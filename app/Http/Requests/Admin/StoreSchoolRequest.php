<?php

namespace App\Http\Requests\Admin;

use App\Support\Uploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => strtolower(trim((string) $this->input('slug'))),
            'admin_email' => strtolower(trim((string) $this->input('admin_email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::notIn(config('skooline.reserved_slugs')),
                Rule::unique('schools', 'slug'),
            ],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'lead_id' => ['nullable', 'integer', 'exists:demo_requests,id'],
            'currency' => ['nullable', Rule::in(array_keys(config('currencies')))],
            'address' => ['nullable', 'string', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:120'],
            'principal_email' => ['nullable', 'email', 'max:150'],
            'principal_phone' => ['nullable', 'string', 'max:30'],
            'principal_national_id' => ['nullable', 'string', 'max:40'],
            'principal_qualification' => ['nullable', 'string', 'max:150'],
            'principal_address' => ['nullable', 'string', 'max:255'],
            'principal_bio' => ['nullable', 'string', 'max:1000'],
            'school_photo' => Uploads::PHOTO_RULES,
            'principal_photo' => Uploads::PHOTO_RULES,
            'notes' => ['nullable', 'string', 'max:2000'],

            'plan_id' => ['required', Rule::exists('plans', 'id')],
            'billing_mode' => ['required', Rule::in(['trial', 'paid'])],
            'trial_days' => ['nullable', 'required_if:billing_mode,trial', 'integer', 'min:1', 'max:365'],
            'starts_at' => ['nullable', 'required_if:billing_mode,paid', 'date'],
            'student_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['nullable', Rule::in(['unpaid', 'paid', 'partial', 'waived'])],

            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:150'],
            'admin_password' => ['nullable', 'string', 'min:8', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Use only lowercase letters, numbers and single hyphens (e.g. "city-grammar").',
            'slug.not_in' => 'This address is reserved. Please choose another.',
            'slug.unique' => 'This address is already used by another school.',
        ];
    }
}
