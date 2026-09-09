<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\CombinesPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RegisterEmployerRequest extends FormRequest
{
    use CombinesPhoneNumber;

    protected function prepareForValidation(): void
    {
        $this->combinePhone();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:120'],
            'email'               => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'               => ['required', 'string', 'max:32', 'unique:users,phone'],
            'country_code'        => ['required', 'string', 'size:2', 'exists:countries,code'],
            'password'            => ['required', 'string', 'min:8', 'confirmed'],
            'company_name'        => ['required', 'string', 'max:180'],
            'company_type_id'     => ['nullable', 'integer', 'exists:company_types,id'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            // Nullable on purpose. The form asks for it and marks one card
            // recommended, but a missing plan must never be the thing that stops
            // an account being created — the portal works without one, just with
            // the monthly free tier instead of a plan allowance.
            'package_id'          => ['nullable', 'integer', 'exists:packages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'        => 'An account with this email already exists.',
            'phone.unique'        => 'An account with this phone number already exists.',
            'phone.required'      => 'Please enter your phone number.',
            'company_name.required' => 'Please enter your company name.',
        ];
    }
}
