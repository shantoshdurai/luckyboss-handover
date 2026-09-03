<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_code' => $this->country_code,
            'current_title' => $this->current_title,
            'summary' => $this->professional_summary,
            'location' => $this->current_location,
            'preferred_location' => $this->preferred_location,
            'experience_years' => $this->years_experience,
            'expected_salary' => $this->expected_salary,
            'preferred_currency' => $this->preferred_currency,
            'notice_period' => $this->notice_period,
            'availability' => $this->availability,
            'profile_completion' => $this->profile_completion,
            'is_visible' => (bool) $this->is_visible,
            'profile_photo_url' => $this->profile_photo_path ? asset($this->profile_photo_path) : null,
            'skills' => data_get($this->resume_data, 'skills', []),
        ];
    }
}
