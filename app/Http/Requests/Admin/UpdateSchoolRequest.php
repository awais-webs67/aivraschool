<?php

namespace App\Http\Requests\Admin;

use App\Support\Uploads;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'currency' => ['nullable', Rule::in(array_keys(config('currencies')))],
            'address' => ['nullable', 'string', 'max:255'],
            'principal_name' => ['nullable', 'string', 'max:120'],
            'principal_email' => ['nullable', 'email', 'max:150'],
            'principal_phone' => ['nullable', 'string', 'max:30'],
            'principal_national_id' => ['nullable', 'string', 'max:40'],
            'principal_qualification' => ['nullable', 'string', 'max:150'],
            'principal_address' => ['nullable', 'string', 'max:255'],
            'principal_bio' => ['nullable', 'string', 'max:1000'],
            'student_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'school_photo' => Uploads::PHOTO_RULES,
            'principal_photo' => Uploads::PHOTO_RULES,
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
