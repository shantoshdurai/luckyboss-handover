<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Offer;
use App\Models\Interview;
use App\Models\PlatformNotification;
use App\Services\AIRecruitmentEngineService;
use App\Services\JobApplicationSubmitter;
use App\Services\JobMatchService;
use App\Services\NotificationService;
use App\Services\SiteSettingsService;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('job-seeker')) {
            if ($user?->hasRole('super-admin')) {
                return redirect()->route('admin.dashboard')->with('info', 'Logged in as Administrator. Redirected to Admin Command Center.');
            }
            if ($user?->hasRole('employer')) {
                return redirect()->route('employer.home')->with('info', 'Logged in as Employer. Redirected to the Employer Portal.');
            }
            return redirect()->route('login')->with('info', 'Please sign in as a Job Seeker to access this portal.');
        }

        $tab = $request->string('tab')->toString() ?: 'dashboard';
        $applications = $user->applications()->with('job.company')->latest('applied_at')->get();
        $offers = Offer::with('application.job.company')->whereHas('application', fn ($query) => $query->where('candidate_id', $user->id))->latest()->get();
        $interviews = Interview::with('application.job.company')->whereHas('application', fn ($query) => $query->where('candidate_id', $user->id))->latest('scheduled_at')->get();
        $savedJobIds = $user->savedJobs()->pluck('job_id')->all();
        $savedJobs = Job::with('company')->whereIn('id', $savedJobIds)->get();

        // Every open vacancy, before any personalisation. This used to be handed
        // straight to the view as "recommendedJobs" and labelled "Curated ...
        // based on your location and background" - it was ordered by publish
        // date and had never been compared to the candidate at all. A seeker who
        // had just signed up and told us nothing still saw six jobs presented as
        // chosen for them.
        $openJobs = Job::with('company')->where('status', 'published')->latest('published_at')->get();

        $matcher = app(JobMatchService::class);
        $settings = app(SiteSettingsService::class)->matching();

        // Can we match this person yet? If not, the view asks for a resume
        // instead of pretending. readiness() also tells it what is missing, so
        // the prompt is specific rather than a generic "complete your profile".
        $readiness = $matcher->readiness($user);

        $matchedJobs = $readiness['ready']
            ? $matcher->rank($openJobs, $user, $settings['minimum_match_score'])
            : collect();

        return view('seeker.dashboard', [
            'user' => $user,
            'tab' => $tab,
            'profile' => $user->candidateProfile,
            'applications' => $applications,
            'matchReadiness' => $readiness,
            'matchSettings' => $settings,
            'recommendedJobs' => $matchedJobs->take(6),
            'allMatchingJobs' => $matchedJobs,
            // Kept separate and never described as recommendations: this is the
            // browse-everything list, which a seeker with an empty profile is
            // still entitled to see.
            'openJobs' => $openJobs,
            'savedJobIds' => $savedJobIds,
            'savedJobs' => $savedJobs,
            'appliedJobIds' => $applications->pluck('job_id')->all(),
            'offers' => $offers,
            'interviews' => $interviews,
            'unreadNotifications' => PlatformNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
            'stats' => [
                'applications' => $applications->count(),
                'shortlisted' => $applications->where('status', 'Shortlisted')->count(),
                'interviews' => $interviews->where('scheduled_at', '>=', now())->count(),
                'offers' => $offers->whereIn('status', ['sent', 'accepted'])->count()
            ],
        ]);
    }

    public function apply(Request $request, Job $job): RedirectResponse
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('job-seeker')) {
            return redirect()->route('login')->with('info', 'Please sign in as a Job Seeker to apply for jobs.');
        }

        abort_unless($job->status === 'published', 404);

        $application = $this->submit($job, $user);
        $score = $application->match_score === null ? null : (int) $application->match_score;

        return back()->with('success', $score === null
            ? "Application for {$job->title} submitted."
            : "Application for {$job->title} submitted — a {$score}% match to your profile.");
    }

    /**
     * Apply to every vacancy currently matching above the admin threshold.
     *
     * The feature sir asked for: one button on the matched list rather than
     * tapping through thirty jobs one at a time. It is deliberately narrow —
     *
     *   - only vacancies on Lucky Boss, never a third-party board;
     *   - only jobs that scored above the threshold, so it cannot become
     *     "apply to everything";
     *   - capped by the admin's bulk_apply_limit, so one tap cannot put a
     *     candidate in front of every employer on the platform at once;
     *   - a POST from an explicit tap, never automatic.
     *
     * The candidate is told exactly how many went out, and firstOrCreate means
     * a double submit re-applies to nothing.
     */
    public function applyAll(Request $request): RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->hasRole('job-seeker')) {
            return redirect()->route('login')->with('info', 'Please sign in as a Job Seeker to apply for jobs.');
        }

        $settings = app(SiteSettingsService::class)->matching();
        if (! $settings['bulk_apply_enabled']) {
            return back()->with('info', 'Applying to several jobs at once is currently switched off.');
        }

        $matcher = app(JobMatchService::class);
        if (! $matcher->readiness($user)['ready']) {
            return back()->with('info', 'Add your skills or upload your resume first, so we know which jobs to apply to.');
        }

        $alreadyApplied = $user->applications()->pluck('job_id')->all();

        $targets = $matcher->rank(
            Job::with('company')->where('status', 'published')->whereNotIn('id', $alreadyApplied)->get(),
            $user,
            $settings['minimum_match_score']
        )->take($settings['bulk_apply_limit']);

        if ($targets->isEmpty()) {
            return back()->with('info', "No new jobs above {$settings['minimum_match_score']}% right now. We will keep looking.");
        }

        foreach ($targets as $job) {
            $this->submit($job, $user);
        }

        $count = $targets->count();

        // Records the tap itself, quantity 1, distinct from the per-application
        // `apply` rows written inside submit().
        app(SubscriptionEntitlementService::class)
            ->consume($user, 'bulk_apply', 1, null, "Apply All sent {$count} applications");

        return back()->with('success', $count === 1
            ? 'Applied to 1 matching job.'
            : "Applied to {$count} matching jobs.");
    }

    /**
     * Creates one application and notifies both sides.
     *
     * The work moved to App\Services\JobApplicationSubmitter when auto-apply
     * became a third caller — it runs from a console command, with no request
     * and no session, and must produce an application indistinguishable from a
     * hand-made one. This wrapper stays so apply()/applyAll() read unchanged.
     */
    private function submit(Job $job, \App\Models\User $user): JobApplication
    {
        return app(JobApplicationSubmitter::class)->submit($job, $user);
    }

    public function withdraw(JobApplication $application): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('job-seeker'), 403);
        abort_unless($application->candidate_id === auth()->id(), 403);

        $application->update(['status' => 'Withdrawn', 'last_activity_at' => now()]);
        return back()->with('success', 'Application withdrawn.');
    }
}