<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A vacancy as the mobile apps and the public feed see it.
 *
 * The last six fields were added on the live deployment and were missing here.
 * They are not cosmetic: `company_logo_url` and `image_url` are what a job card
 * renders, `description` is the whole detail screen, and `published_at` is what
 * "posted 3 days ago" is calculated from. Merging the two versions rather than
 * overwriting either is why they are back.
 */
class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'title' => $this->title, 'company' => $this->company?->name,
            'company_logo_url' => $this->company?->logo_path ? asset($this->company->logo_path) : null,
            'category' => $this->whenLoaded('jobCategory', fn () => $this->jobCategory?->name),
            'description' => $this->description,
            'image_url' => $this->image_path ? asset($this->image_path) : null,
            'location' => $this->location, 'country' => $this->country_code, 'work_mode' => $this->work_mode,
            'job_type' => $this->job_type, 'experience' => [$this->experience_min, $this->experience_max],
            'salary' => $this->salary_visible ? ['min' => $this->salary_min, 'max' => $this->salary_max, 'currency' => $this->currency_code] : null,
            'vacancies' => $this->vacancies,
            'featured' => $this->is_featured, 'urgent' => $this->is_urgent, 'payment_required' => $this->is_paid_apply,
            'application_fee' => $this->is_paid_apply ? $this->application_fee : null, 'closing_date' => $this->closing_date?->toDateString(),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
