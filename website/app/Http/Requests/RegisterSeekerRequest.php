<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterSeekerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:120'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'        => ['required', 'string', 'max:32', 'unique:users,phone'],
            // Not asked for at sign-up any more — sir cut it from the form.
            // Country is collected with the rest of the location detail in the
            // profile wizard, where it is actually used (matching scores on
            // location). Both `users` and `candidate_profiles` allow null, and
            // JobMatchService already treats a missing location as a dimension
            // it cannot assess rather than guessing one.
            'country_code' => ['nullable', 'string', 'size:2', 'exists:countries,code'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'     => 'An account with this email already exists.',
            'phone.unique'     => 'An account with this phone number already exists.',
            'password.min'     => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ];
    }
}
