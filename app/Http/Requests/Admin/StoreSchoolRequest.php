<?php

namespace App\Http\Requests\Admin;

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
            'subdomain' => strtolower(trim((string) $this->input('subdomain'))),
            'admin_email' => strtolower(trim((string) $this->input('admin_email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'subdomain' => [
                'required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                Rule::notIn(config('skooline.reserved_subdomains')),
                Rule::unique('tenants', 'subdomain'),
                Rule::unique('domains', 'domain'),
            ],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:120'],
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
            'subdomain.regex' => 'Use only lowercase letters, numbers and hyphens (e.g. "city-grammar").',
            'subdomain.not_in' => 'This subdomain is reserved. Please choose another.',
            'subdomain.unique' => 'This subdomain is already taken by another school.',
        ];
    }
}
