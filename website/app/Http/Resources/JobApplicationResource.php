<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'status' => $this->status,
            'match_score' => (float) $this->match_score,
            'applied_at' => $this->applied_at?->toIso8601String(),
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'job' => $this->whenLoaded('job', fn (): array => [
                'id' => $this->job->id,
                'title' => $this->job->title,
                'location' => $this->job->location,
                'country_code' => $this->job->country_code,
                'work_mode' => $this->job->work_mode,
                'salary_min' => $this->job->salary_visible ? $this->job->salary_min : null,
                'salary_max' => $this->job->salary_visible ? $this->job->salary_max : null,
                'currency_code' => $this->job->salary_visible ? $this->job->currency_code : null,
                'image_url' => $this->job->image_path ? asset($this->job->image_path) : null,
                'company' => $this->when(
                    $this->job->relationLoaded('company'),
                    fn () => ['id' => $this->job->company?->id, 'name' => $this->job->company?->name]
                ),
            ]),
            'candidate' => $this->whenLoaded('candidate', fn (): array => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'email' => $this->candidate->email,
                'phone' => $this->candidate->phone,
                'country_code' => $this->candidate->country_code,
                'candidate_profile' => $this->when(
                    $this->candidate->relationLoaded('candidateProfile') && $this->candidate->candidateProfile,
                    fn () => new CandidateProfileResource($this->candidate->candidateProfile)
                ),
            ]),
        ];
    }
}
