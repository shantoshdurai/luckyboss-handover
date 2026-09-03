<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployerJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->whenLoaded('jobCategory', fn () => $this->jobCategory?->name),
            'location' => $this->location,
            'country_code' => $this->country_code,
            'work_mode' => $this->work_mode,
            'job_type' => $this->job_type,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'currency_code' => $this->currency_code,
            'description' => $this->description,
            'image_url' => $this->image_path ? asset($this->image_path) : null,
            'applications_count' => $this->applications_count ?? 0,
            'status' => $this->status,
            'vacancies' => $this->vacancies,
            'closing_date' => $this->closing_date?->toDateString(),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
