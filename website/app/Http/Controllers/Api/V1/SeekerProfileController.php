<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The candidate's own profile — read and write.
 *
 * This is what stops the app asking for the same details twice. Everything the
 * onboarding wizard and the profile editors collect is persisted here, and
 * [show] hands it straight back on the next launch.
 *
 * Writes are a partial update by design: the profile editors save one field at
 * a time, and a candidate correcting their headline must not have their skills
 * wiped because the client did not resend them.
 */
class SeekerProfileController extends Controller
{
    /** GET /api/v1/job-seeker/profile */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = CandidateProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['country_code' => $user->country_code ?? 'SG', 'profile_completion' => 20]
        );

        return response()->json(['data' => $this->present($user, $profile)]);
    }

    /** PUT /api/v1/job-seeker/profile */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'string', 'max:32'],
            'headline' => ['sometimes', 'nullable', 'string', 'max:180'],
            'professional_summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'department' => ['sometimes', 'nullable', 'string', 'max:120'],
            'preferred_category' => ['sometimes', 'nullable', 'string', 'max:120'],
            'current_title' => ['sometimes', 'nullable', 'string', 'max:150'],
            'current_location' => ['sometimes', 'nullable', 'string', 'max:120'],
            'preferred_location' => ['sometimes', 'nullable', 'string', 'max:120'],
            'expected_salary' => ['sometimes', 'nullable', 'string', 'max:32'],
            'availability' => ['sometimes', 'nullable', 'string', 'max:60'],
            'notice_period' => ['sometimes', 'nullable', 'string', 'max:60'],
            'years_experience' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:60'],
            'qualification' => ['sometimes', 'nullable', 'string', 'max:80'],
            'course' => ['sometimes', 'nullable', 'string', 'max:120'],
            'passing_year' => ['sometimes', 'nullable', 'string', 'max:8'],
            'resume_file_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_student' => ['sometimes', 'boolean'],
            'open_to_relocate' => ['sometimes', 'nullable', 'boolean'],
            'has_work_permit' => ['sometimes', 'nullable', 'boolean'],
            'skills' => ['sometimes', 'array', 'max:60'],
            'skills.*' => ['string', 'max:120'],
            'projects' => ['sometimes', 'array', 'max:30'],
            'projects.*' => ['string', 'max:200'],
            'languages' => ['sometimes', 'array', 'max:20'],
            'languages.*' => ['string', 'max:60'],
            'work_modes' => ['sometimes', 'array', 'max:5'],
            'work_modes.*' => ['string', 'max:30'],
            'job_types' => ['sometimes', 'array', 'max:6'],
            'job_types.*' => ['string', 'max:30'],
        ]);

        $user = $request->user();

        // Name and phone live on the user, not the profile.
        $userFields = array_intersect_key($data, array_flip(['name', 'phone']));
        if ($userFields !== []) {
            $user->fill($userFields)->save();
        }

        $profile = CandidateProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['country_code' => $user->country_code ?? 'SG']
        );

        $profileFields = array_diff_key($data, array_flip(['name', 'phone']));
        if ($profileFields !== []) {
            $profile->fill($profileFields);
        }

        $profile->profile_completion = $this->completion($user, $profile);
        $profile->save();

        return response()->json(['data' => $this->present($user->fresh(), $profile)]);
    }

    /**
     * Server-side completion score.
     *
     * Mirrors the weights the app shows on its boost cards. Recomputed here so
     * the employer-facing number cannot drift from what the candidate was told
     * — the client is not trusted to report its own score.
     */
    private function completion($user, CandidateProfile $p): int
    {
        $weights = [
            'name' => [5, fn () => filled($user->name)],
            'email' => [5, fn () => filled($user->email)],
            'phone' => [5, fn () => filled($user->phone)],
            'skills' => [20, fn () => filled($p->skills)],
            'resume' => [15, fn () => filled($p->resume_file_name)],
            'headline' => [8, fn () => filled($p->headline)],
            'bio' => [10, fn () => filled($p->professional_summary)],
            'category' => [6, fn () => filled($p->preferred_category)],
            'department' => [6, fn () => filled($p->department)],
            'photo' => [5, fn () => filled($p->profile_photo_path)],
            'city' => [5, fn () => filled($p->current_location)],
            'salary' => [4, fn () => filled($p->expected_salary)],
            'projects' => [3, fn () => filled($p->projects)],
            'languages' => [3, fn () => filled($p->languages)],
        ];

        $earned = 0;
        foreach ($weights as [$weight, $test]) {
            if ($test()) {
                $earned += $weight;
            }
        }

        return min(100, $earned);
    }

    private function present($user, CandidateProfile $p): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'country_code' => $p->country_code,
            'headline' => $p->headline,
            'professional_summary' => $p->professional_summary,
            'department' => $p->department,
            'preferred_category' => $p->preferred_category,
            'current_title' => $p->current_title,
            'current_location' => $p->current_location,
            'preferred_location' => $p->preferred_location,
            'expected_salary' => $p->expected_salary,
            'availability' => $p->availability,
            'notice_period' => $p->notice_period,
            'years_experience' => $p->years_experience,
            'qualification' => $p->qualification,
            'course' => $p->course,
            'passing_year' => $p->passing_year,
            'resume_file_name' => $p->resume_file_name,
            'is_student' => (bool) $p->is_student,
            'open_to_relocate' => $p->open_to_relocate,
            'has_work_permit' => $p->has_work_permit,
            'skills' => $p->skills ?? [],
            'projects' => $p->projects ?? [],
            'languages' => $p->languages ?? [],
            'work_modes' => $p->work_modes ?? [],
            'job_types' => $p->job_types ?? [],
            'photo_url' => $p->profile_photo_path,
            'profile_completion' => $p->profile_completion,
        ];
    }
}
