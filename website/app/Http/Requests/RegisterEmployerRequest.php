<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterEmployerRequest extends FormRequest
{
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
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'        => 'An account with this email already exists.',
            'phone.unique'        => 'An account with this phone number already exists.',
            'company_name.required' => 'Please enter your company name.',
        ];
    }
}
