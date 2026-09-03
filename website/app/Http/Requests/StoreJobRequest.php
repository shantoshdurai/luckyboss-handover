<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:200'],
            'category_id'      => ['required', 'integer', 'exists:job_categories,id'],
            'description'      => ['required', 'string', 'min:50'],
            'requirements'     => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'location'         => ['required', 'string', 'max:255'],
            'country_code'     => ['required', 'string', 'exists:countries,code'],
            'job_type'         => ['required', 'string', 'in:full-time,part-time,contract,internship,temporary'],
            'work_mode'        => ['nullable', 'string', 'in:onsite,remote,hybrid'],
            'experience_min'   => ['nullable', 'integer', 'min:0'],
            'experience_max'   => ['nullable', 'integer', 'min:0', 'gte:experience_min'],
            'salary_min'       => ['nullable', 'numeric', 'min:0'],
            'salary_max'       => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'currency_code'    => ['nullable', 'string', 'exists:currencies,code'],
            'vacancies'        => ['nullable', 'integer', 'min:1'],
            'closing_date'     => ['nullable', 'date', 'after:today'],
            'skills'           => ['nullable', 'string'],
            'image'            => ['nullable', 'image', 'max:2048'],
        ];
    }
}
