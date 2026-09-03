<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Models\CandidateResume;

class ProfileController extends Controller
{
    private const JSON_SECTIONS = ['personal', 'summary', 'experience', 'education', 'skills', 'projects', 'certifications', 'languages', 'current_employment', 'job_preferences', 'mobility', 'international_jobs', 'achievements', 'online_profiles', 'references', 'documents', 'declaration'];

    private function candidate(): void
    {
        abort_unless(auth()->user()?->hasRole('job-seeker'), 403);
    }

    public function edit(): View
    {
        $this->candidate();
        $user = auth()->user()->load('candidateProfile');
        $profile = $user->candidateProfile;

        $skills = [];
        if (is_array($profile?->resume_data) && !empty($profile->resume_data['skills'])) {
            $skills = is_array($profile->resume_data['skills']) 
                ? $profile->resume_data['skills'] 
                : array_filter(array_map('trim', explode(',', (string) $profile->resume_data['skills'])));
        }

        $initialData = [
            'currentTitle' => (string) ($profile?->current_title ?: ''),
            'yearsExperience' => (int) ($profile?->years_experience ?? 0),
            'professionalSummary' => (string) ($profile?->professional_summary ?: ''),
            'currentLocation' => (string) ($profile?->current_location ?: 'Singapore'),
            'expectedSalary' => (int) ($profile?->expected_salary ?: 0),
            'noticePeriod' => (string) ($profile?->notice_period ?: 'Immediate / 1 Month'),
            'skills' => array_values(array_unique($skills)),
        ];

        return view('seeker.profile.edit', [
            'user' => $user, 
            'profile' => $profile,
            'initialDataJson' => json_encode($initialData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->candidate();
        $user = auth()->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'whatsapp_number' => ['nullable', 'string', 'max:32'],
            'ai_summary_notes' => ['nullable', 'string', 'max:2000'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
            'resume_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,txt', 'max:10240'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:40'],
            'country_code' => ['nullable', 'string', 'max:3'],
            'current_title' => ['nullable', 'string', 'max:180'],
            'professional_summary' => ['nullable', 'string', 'max:5000'],
            'current_location' => ['nullable', 'string', 'max:180'],
            'preferred_location' => ['nullable', 'string', 'max:180'],
            'years_experience' => ['nullable', 'integer', 'min:0'],
            'current_salary' => ['nullable', 'numeric', 'min:0'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'preferred_currency' => ['nullable', 'string', 'size:3'],
            'notice_period' => ['nullable', 'string', 'max:80'],
            'availability' => ['nullable', 'string', 'max:120'],
            'skills' => ['nullable'],
        ]);

        $profile = $user->candidateProfile()->firstOrCreate([], ['country_code' => 'SG', 'profile_completion' => 0]);

        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $directory = public_path('uploads/candidates');
            if (! is_dir($directory)) mkdir($directory, 0755, true);
            $name = 'profile-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$file->extension();
            $file->move($directory, $name);
            $data['profile_photo_path'] = 'uploads/candidates/'.$name;
        }

        if ($request->hasFile('resume_file')) {
            $file = $request->file('resume_file');
            CandidateResume::create([
                'candidate_id' => $user->id,
                'file_path' => $file->store('candidate-resumes'),
                'file_name' => $file->getClientOriginalName(),
                'parse_status' => 'auto-parsed'
            ]);
        }

        $whatsappNumber = $data['whatsapp_number'] ?? null;
        $aiSummaryNotes = $data['ai_summary_notes'] ?? null;
        $skillsInput = $data['skills'] ?? null;

        unset($data['name'], $data['phone'], $data['email'], $data['whatsapp_number'], $data['ai_summary_notes'], $data['profile_photo'], $data['resume_file'], $data['skills']);

        $resumeData = is_array($profile->resume_data) ? $profile->resume_data : [];
        $resumeData['personal'] = ['whatsapp_number' => $whatsappNumber];
        $resumeData['summary'] = ['ai_notes' => $aiSummaryNotes];

        if (!empty($skillsInput)) {
            if (is_string($skillsInput)) {
                $decoded = json_decode($skillsInput, true);
                $resumeData['skills'] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) 
                    ? array_values($decoded) 
                    : array_filter(array_map('trim', explode(',', $skillsInput)));
            } elseif (is_array($skillsInput)) {
                $resumeData['skills'] = array_values($skillsInput);
            }
        }

        foreach (self::JSON_SECTIONS as $section) {
            if ($section !== 'skills' && $request->filled($section)) {
                $decoded = json_decode($request->input($section), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $resumeData[$section] = $decoded;
                }
            }
        }

        $data['resume_data'] = $resumeData;
        $user->update($request->only(['name', 'phone', 'email']));
        $data['profile_completion'] = $this->completion($data, $resumeData);
        $profile->update($data);

        return back()->with('success', 'Candidate profile and resume saved successfully.');
    }

    public function parseResume(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->candidate();
        $request->validate([
            'resume_file' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
        ]);

        $file = $request->file('resume_file');
        $result = app(\App\Services\AIRecruitmentEngineService::class)->parseResumeFile($file);

        return response()->json([
            'status' => 'success',
            'data' => $result,
            'message' => 'Resume extracted and parsed successfully with AI.',
        ]);
    }

    private function completion(array $data, array $resumeData): int
    {
        $fields = ['name', 'phone', 'email', 'date_of_birth', 'gender', 'current_title', 'professional_summary', 'current_location', 'preferred_location', 'years_experience', 'notice_period'];
        $completed = collect($fields)->filter(fn ($field) => filled($data[$field] ?? null) || filled(data_get(auth()->user(), $field)))->count();
        $sections = collect(self::JSON_SECTIONS)->filter(fn ($section) => ! empty($resumeData[$section]))->count();
        return min(100, (int) round(($completed + $sections) / (count($fields) + count(self::JSON_SECTIONS)) * 100));
    }
}
