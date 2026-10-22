<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'modules' => array_values((array) $this->input('modules', [])),
            'is_active' => $this->boolean('is_active'),
            'show_on_website' => $this->boolean('show_on_website'),
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    public function rules(): array
    {
        $plan = $this->route('plan');

        return [
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:80', Rule::unique('plans', 'slug')->ignore($plan?->id)],
            'tagline' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_model' => ['required', Rule::in(array_keys(config('skooline.billing_models')))],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'price_per_student' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'setup_fee' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'max_students' => ['nullable', 'integer', 'min:1'],
            'max_staff' => ['nullable', 'integer', 'min:1'],
            'max_branches' => ['required', 'integer', 'min:1', 'max:1000'],
            'storage_gb' => ['required', 'integer', 'min:1', 'max:100000'],
            'sms_credits' => ['nullable', 'integer', 'min:0'],
            'modules' => ['array'],
            'modules.*' => ['string', Rule::in(array_keys(config('skooline.modules')))],
            'is_active' => ['boolean'],
            'show_on_website' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    /** Validated data with empty optional numbers normalised. */
    public function planData(): array
    {
        $data = $this->validated();

        foreach (['price_per_student', 'setup_fee', 'sms_credits', 'sort_order'] as $key) {
            $data[$key] = $data[$key] ?? 0;
        }

        return $data;
    }
}
