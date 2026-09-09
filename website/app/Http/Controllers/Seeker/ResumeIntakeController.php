<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\JobMatchService;
use App\Services\ResumeIntakeService;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Resume-first onboarding: upload → read → check → matched jobs → Apply All.
 *
 * Sir asked for this twice, most plainly in the 2026-09-04 note: "Resume Upload
 * பண்ண உடனே அது parsing பண்ணிட்டு calculate பண்ணிடுது. எந்த Job என்ன role அதுக்கு
 * relevant Job எல்லாமே காட்டுது." The point is that it is **one continuous
 * motion**. Parsing a CV and then returning the candidate to a dashboard to
 * discover their matches on their own is not the feature he described.
 *
 * TickBig offers the same two doors — build from scratch, or upload an existing
 * CV — but makes the builder primary. Ours is inverted on purpose: the
 * electrician with a one-page CV on WhatsApp is our modal user; the student with
 * no CV at all is theirs.
 *
 * The extracted values are never saved silently. `requires_review` from
 * ResumeIntakeService is contractual: the candidate sees every field and
 * confirms it. A parser that writes straight to a profile puts a fabricated
 * employer in front of a real hiring manager.
 */
class ResumeIntakeController extends Controller
{
    /** Where the pending, unconfirmed extraction lives between the two requests. */
    private const REVIEW_KEY = 'resume_intake.pending';

    private function candidate(): void
    {
        abort_unless(auth()->user()?->hasRole('job-seeker'), 403);
    }

    /** The two doors. */
    public function choose(ResumeIntakeService $intake): View
    {
        $this->candidate();

        return view('seeker.resume.choose', [
            'profile' => auth()->user()->candidateProfile,
            // Shown plainly rather than hidden: if autofill is off, the upload
            // still works and still keeps the file, and the candidate is told
            // they will be typing the details themselves.
            'autofillAvailable' => $intake->autofillAvailable(),
        ]);
    }

    public function upload(Request $request, ResumeIntakeService $intake): RedirectResponse
    {
        $this->candidate();

        $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx,txt', 'max:'.ResumeIntakeService::MAX_KB],
        ], [
            'resume.mimes' => 'Upload a PDF or Word document.',
            'resume.max' => 'That file is too large. Keep it under 4 MB.',
        ]);

        $result = $intake->intake($request->file('resume'), auth()->user());

        // Straight to review in every outcome. Even when nothing could be read,
        // the file is saved and the review form is where they fill the gaps —
        // which is a better place to land than back on the upload box with an
        // error and no way forward.
        return redirect()
            ->route('seeker.resume.review')
            ->with(self::REVIEW_KEY, $result)
            ->with($result['status'] === 'success' ? 'success' : 'info', $result['message']);
    }

    /**
     * Every extracted value, beside an editable field.
     *
     * Falls back to whatever is already on the profile when there is no pending
     * extraction — so this page is also a plain "check my details" screen, and a
     * refresh does not empty it.
     */
    public function review(): View
    {
        $this->candidate();

        $user = auth()->user();
        $profile = $user->candidateProfile;
        $pending = session(self::REVIEW_KEY);
        $extracted = is_array($pending) ? ($pending['data'] ?? null) : null;

        $existingSkills = $this->profileSkills($profile);

        return view('seeker.resume.review', [
            'user' => $user,
            'profile' => $profile,
            // True only when a parse actually produced values. Drives the
            // "check these before saving" framing; without it the page must not
            // imply anything was read.
            'fromParse' => $extracted !== null,
            'resume' => is_array($pending) ? ($pending['resume'] ?? null) : null,
            'values' => [
                'name' => $extracted['name'] ?? $user->name,
                'email' => $extracted['email'] ?? $user->email,
                'phone' => $extracted['phone'] ?? $user->phone,
                'current_title' => $extracted['current_title'] ?? $profile?->current_title,
                'years_experience' => $extracted['years_experience'] ?? $profile?->years_experience,
                'current_location' => $extracted['current_city'] ?? $profile?->current_location,
                'professional_summary' => $extracted['summary'] ?? $profile?->professional_summary,
                'qualification' => $extracted['qualification'] ?? $profile?->qualification,
                'course' => $extracted['course'] ?? $profile?->course,
                'passing_year' => $extracted['passing_year'] ?? $profile?->passing_year,
            ],
            'skills' => ! empty($extracted['skills']) ? $extracted['skills'] : $existingSkills,
        ]);
    }

    /** The candidate has checked it. Only now does anything reach the profile. */
    public function confirm(Request $request): RedirectResponse
    {
        $this->candidate();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:32'],
            'current_title' => ['nullable', 'string', 'max:180'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'current_location' => ['nullable', 'string', 'max:180'],
            'professional_summary' => ['nullable', 'string', 'max:5000'],
            'qualification' => ['nullable', 'string', 'max:80'],
            'course' => ['nullable', 'string', 'max:180'],
            'passing_year' => ['nullable', 'string', 'max:10'],
            'skills' => ['nullable', 'string'],
        ]);

        $user = auth()->user();
        $profile = $user->candidateProfile()->firstOrCreate([], ['country_code' => 'SG', 'profile_completion' => 0]);

        $skills = $this->decodeSkills($data['skills'] ?? null);

        $resumeData = is_array($profile->resume_data) ? $profile->resume_data : [];
        $resumeData['skills'] = $skills;

        $profile->fill([
            'current_title' => $data['current_title'] ?? null,
            'years_experience' => $data['years_experience'] ?? null,
            'current_location' => $data['current_location'] ?? null,
            'professional_summary' => $data['professional_summary'] ?? null,
            'qualification' => $data['qualification'] ?? null,
            'course' => $data['course'] ?? null,
            'passing_year' => $data['passing_year'] ?? null,
            'resume_data' => $resumeData,
        ]);

        // `skills` exists as its own array-cast column as well as inside
        // resume_data, and JobMatchService reads both. Writing one and not the
        // other is how a candidate ends up unmatched immediately after telling
        // us their trade.
        $profile->skills = $skills;
        $profile->save();

        $user->forceFill(array_filter([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]))->save();

        return redirect()
            ->route('seeker.resume.matches')
            ->with('success', 'Saved. Here is what fits you right now.');
    }

    /**
     * The payoff, and the reason the flow exists: matches, immediately.
     */
    public function matches(JobMatchService $matcher, SiteSettingsService $settings): View
    {
        $this->candidate();

        $user = auth()->user();
        $matching = $settings->matching();
        $readiness = $matcher->readiness($user);

        $applied = $user->applications()->pluck('job_id')->all();

        $matched = $readiness['ready']
            ? $matcher->rank(
                Job::with('company')->where('status', 'published')->get(),
                $user,
                $matching['minimum_match_score']
            )
            : collect();

        return view('seeker.resume.matches', [
            'user' => $user,
            'profile' => $user->candidateProfile,
            'matchReadiness' => $readiness,
            'matchSettings' => $matching,
            'matchedJobs' => $matched,
            'appliedJobIds' => $applied,
            // What Apply All would actually do if tapped right now, so the
            // confirm text can state a real number instead of "several".
            'applyAllCount' => min(
                $matched->reject(fn (Job $job) => in_array($job->id, $applied, true))->count(),
                $matching['bulk_apply_limit']
            ),
        ]);
    }

    /** @return list<string> */
    private function profileSkills(?object $profile): array
    {
        $raw = $profile?->resume_data['skills'] ?? $profile?->skills ?? [];

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : array_map('trim', explode(',', $raw));
        }

        return is_array($raw) ? array_values(array_filter(array_map('strval', $raw))) : [];
    }

    /** @return list<string> */
    private function decodeSkills(?string $input): array
    {
        if (blank($input)) {
            return [];
        }

        $decoded = json_decode($input, true);
        $list = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
            ? $decoded
            : explode(',', $input);

        return collect($list)
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->unique()
            ->take(40)
            ->values()
            ->all();
    }
}
