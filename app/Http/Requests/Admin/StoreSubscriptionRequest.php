<?php

namespace App\Http\Requests\Admin;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', Rule::exists('plans', 'id')],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'student_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', Rule::in(Subscription::PAYMENT_STATUSES)],
            'payment_method' => ['nullable', Rule::in(Subscription::PAYMENT_METHODS)],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
