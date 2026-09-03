<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PortalController extends Controller
{
    public function seekerDashboard(Request $request)
    {
        $this->role($request, 'job-seeker'); $user = $request->user();
        return response()->json(['profile' => $user->candidateProfile, 'applications' => $user->applications()->with('job.company')->latest('applied_at')->get(), 'recommended_jobs' => Job::with('company')->where('status', 'published')->take(10)->get()]);
    }

    public function apply(Request $request, Job $job)
    {
        $this->role($request, 'job-seeker'); abort_unless($job->status === 'published', 404);
        abort_if($job->is_paid_apply, 422, 'Payment is required before applying to this job.');
        
        $match = app(\App\Services\AIRecruitmentEngineService::class)->calculateMatch($job, $request->user());
        
        $application = JobApplication::firstOrCreate(
            ['job_id' => $job->id, 'candidate_id' => $request->user()->id],
            ['status' => 'New', 'match_score' => $match['score'], 'applied_at' => now(), 'last_activity_at' => now()]
        );
        
        return response()->json([
            'application' => $application, 
            'match' => $match,
            'created' => $application->wasRecentlyCreated
        ], $application->wasRecentlyCreated ? 201 : 200);
    }

    public function employerDashboard(Request $request)
    {
        $this->role($request, 'employer');
        $company = $request->user()->companies()->firstOrFail();
        $jobs = Job::where('company_id', $company->id)->withCount('applications')->latest()->get();
        return response()->json([
            'company' => $company,
            'jobs' => $jobs,
            'totals' => [
                'active_jobs' => $jobs->where('status', 'published')->count(),
                'applications' => $jobs->sum('applications_count'),
            ]
        ]);
    }

    public function employerCandidates(Request $request, ?Job $job = null)
    {
        $this->role($request, 'employer');
        $company = $request->user()->companies()->firstOrFail();

        $query = JobApplication::whereHas('job', function ($q) use ($company, $job) {
            $q->where('company_id', $company->id);
            if ($job) {
                $q->where('id', $job->id);
            }
        })->with(['candidate.candidateProfile', 'job']);

        // Whether this plan may see contact details at all. Spec §14: "Phone
        // and email should be shown based on package permission and candidate
        // privacy rules." This used to be hardcoded true, so every plan saw
        // every number and the contact-view limit sold in the packages meant
        // nothing.
        $entitlements = $company->subscriptions()
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', today())
            ->latest('expires_at')
            ->value('entitlements') ?? [];

        $maySeeContacts = (int) data_get($entitlements, 'candidate_views', 0) !== 0;

        $candidates = $query->latest('applied_at')->get()->map(function ($app) use ($maySeeContacts) {
            $profile = $app->candidate?->candidateProfile;

            // NOTHING BELOW IS INVENTED.
            //
            // This map used to substitute a default for every missing field: a
            // phone number of "+65 8000 0000", an email of
            // "applicant@example.com", "3 yrs" of experience, a location of
            // "Singapore", skills of ["General"] and an AI match score of 85.
            // An employer saw a complete, confident profile of a person who had
            // filled in almost none of it, and would have dialled a number that
            // belongs to nobody.
            //
            // A missing field is now null, and the app shows "Not provided".
            // An incomplete profile is a fact the employer needs — it is the
            // difference between a candidate worth calling and one worth
            // chasing for details first.
            return [
                'id' => 'cand-' . $app->id,
                // The real application id, so the portal can act on this
                // candidate — draft a letter, move their stage — without
                // parsing it back out of a display string.
                'application_id' => $app->id,
                'job_id' => $app->job_id,
                'job_title' => $app->job?->title,
                'candidate_name' => $app->candidate?->name,
                'candidate_phone' => $maySeeContacts ? $app->candidate?->phone : null,
                'candidate_email' => $maySeeContacts ? $app->candidate?->email : null,
                'headline' => $profile?->headline,
                'current_title' => $profile?->current_title,
                'years_experience' => $profile?->years_experience === null ? null : (int) $profile->years_experience,
                'location' => $profile?->current_location,
                'skills' => is_array($profile?->skills) ? $profile->skills : [],
                'languages' => is_array($profile?->languages) ? $profile->languages : [],
                'availability' => $profile?->availability,
                // Null rather than a flattering default. A score of 85 that
                // nothing computed is the most persuasive number on the screen
                // and the least true.
                // Cast, because the column is `numeric` and SQLite hands it
                // back as a string — which a typed client parses as null and
                // then renders as "no match".
                'ai_match_score' => $app->match_score === null ? null : (float) $app->match_score,
                'status' => $app->status,
                'source' => 'applied',
                'contact_revealed' => $maySeeContacts,
                'profile_completion' => $profile?->profile_completion,
                'applied_at' => $app->applied_at,
                'last_activity' => $app->last_activity_at ?? $app->created_at,
            ];
        });

        return response()->json(['candidates' => $candidates]);
    }

    public function updateCandidateStatus(Request $request, JobApplication $application)
    {
        $this->role($request, 'employer');
        $data = $request->validate([
            'status' => 'required|string|max:60',
            'archive_reason' => 'nullable|string|max:120',
        ]);

        $application->update([
            'status' => $data['status'],
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'message' => 'Candidate status updated successfully.',
            'application' => $application,
        ]);
    }

    /**
     * Posts a vacancy from the employer app.
     *
     * This was returning HTTP 500 for every single call — it referenced
     * App\Models\Category, which does not exist. The core feature of the
     * employer portal had never worked once.
     *
     * Three other things were wrong behind that error and would have surfaced
     * the moment it was fixed:
     *
     * 1. A user with no company fell back to `Company::first()`, so a vacancy
     *    could be published under somebody else's business.
     * 2. `min_salary`, `max_salary`, `category_id` and `slug` are not columns on
     *    this table. Mass assignment drops them silently, so every job would
     *    have posted with no salary at all.
     * 3. The missing values were invented: a salary of 4,000-7,000, a location
     *    of Singapore and a description of "Position details". Candidates would
     *    have applied for money no employer had offered.
     *
     * Nothing is invented now. A field the employer left blank stays blank, and
     * the salary is hidden rather than guessed.
     */
    public function postEmployerJob(Request $request)
    {
        $this->role($request, 'employer');

        $data = $request->validate([
            'title' => 'required|string|max:190',
            'category' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:190',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'currency_code' => 'nullable|string|size:3',
            'country_code' => 'nullable|string|size:2',
            'work_mode' => 'nullable|string|max:50',
            'job_type' => 'nullable|string|max:50',
            'experience_min' => 'nullable|integer|min:0',
            'experience_max' => 'nullable|integer|min:0',
            'vacancies' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $company = $request->user()?->companies()->first();

        // Refused rather than guessed. Publishing under whichever company
        // happened to be first in the table put one business's vacancy on
        // another's profile.
        if ($company === null) {
            return response()->json([
                'message' => 'This account is not linked to a company yet. Complete your company profile before posting a vacancy.',
            ], 422);
        }

        // Matched on what the employer chose. Falling back to the first
        // category in the table filed warehouse jobs under whatever happened to
        // be seeded first, and the feed is browsed by category.
        $categoryId = null;
        if (! empty($data['category'])) {
            $categoryId = JobCategory::where('name', $data['category'])
                ->orWhere('slug', Str::slug($data['category']))
                ->value('id');
        }

        $job = Job::create([
            'company_id' => $company->id,
            'job_category_id' => $categoryId,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'location' => $data['location'] ?? null,
            'country_code' => $data['country_code'] ?? $company->country_code ?? 'SG',
            'currency_code' => $data['currency_code'] ?? 'SGD',
            'salary_min' => $data['salary_min'] ?? null,
            'salary_max' => $data['salary_max'] ?? null,
            // Nothing to show is not the same as choosing to hide it, but
            // publishing an invented range is worse than publishing none.
            'salary_visible' => isset($data['salary_min']),
            'work_mode' => $data['work_mode'] ?? 'on-site',
            'job_type' => $data['job_type'] ?? 'full-time',
            'experience_min' => $data['experience_min'] ?? null,
            'experience_max' => $data['experience_max'] ?? null,
            'vacancies' => $data['vacancies'] ?? 1,
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json([
            'message' => 'Job posted successfully.',
            'job' => new JobResource($job->load(['company', 'jobCategory'])),
        ], 201);
    }

    private function role(Request $request, string $role): void
    {
        abort_unless($request->user()?->hasRole($role), 403);
    }
}