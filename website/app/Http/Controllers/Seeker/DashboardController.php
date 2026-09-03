<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Offer;
use App\Models\Interview;
use App\Models\PlatformNotification;
use App\Services\AIRecruitmentEngineService;
use App\Services\NotificationService;
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
                return redirect()->route('employer.dashboard')->with('info', 'Logged in as Employer. Redirected to Employer Portal.');
            }
            return redirect()->route('login')->with('info', 'Please sign in as a Job Seeker to access this portal.');
        }

        $tab = $request->string('tab')->toString() ?: 'dashboard';
        $applications = $user->applications()->with('job.company')->latest('applied_at')->get();
        $offers = Offer::with('application.job.company')->whereHas('application', fn ($query) => $query->where('candidate_id', $user->id))->latest()->get();
        $interviews = Interview::with('application.job.company')->whereHas('application', fn ($query) => $query->where('candidate_id', $user->id))->latest('scheduled_at')->get();
        $savedJobIds = $user->savedJobs()->pluck('job_id')->all();
        $savedJobs = Job::with('company')->whereIn('id', $savedJobIds)->get();
        $allMatchingJobs = Job::with('company')->where('status', 'published')->latest('published_at')->get();

        return view('seeker.dashboard', [
            'user' => $user,
            'tab' => $tab,
            'profile' => $user->candidateProfile,
            'applications' => $applications,
            'recommendedJobs' => $allMatchingJobs->take(6),
            'allMatchingJobs' => $allMatchingJobs,
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

        $match = app(AIRecruitmentEngineService::class)->calculateMatch($job, $user);
        $score = $match['score'] ?? 88;

        $application = JobApplication::firstOrCreate(
            ['job_id' => $job->id, 'candidate_id' => $user->id],
            [
                'status' => 'New',
                'match_score' => $score,
                'applied_at' => now(),
                'last_activity_at' => now(),
                'source' => 'Direct Candidate Portal'
            ]
        );

        // 1. Notify Candidate
        try {
            app(NotificationService::class)->send(
                $user,
                'application_status',
                "Application Submitted: {$job->title}",
                "Your application for {$job->title} at " . ($job->company->name ?? 'Verified Employer') . " has been received ({$score}% AI Match score).",
                ['job_id' => $job->id, 'application_id' => $application->id],
                'job_match'
            );
        } catch (\Throwable $e) {}

        // 2. Notify Employer
        $employerUser = $job->company?->users()->first();
        if ($employerUser) {
            try {
                app(NotificationService::class)->send(
                    $employerUser,
                    'applicant_alert',
                    "New Application: {$user->name}",
                    "{$user->name} applied for {$job->title} ({$score}% match)",
                    ['job_id' => $job->id, 'application_id' => $application->id],
                    'new_candidate'
                );
            } catch (\Throwable $e) {}
        }

        return back()->with('success', "Application for {$job->title} submitted successfully! Verified with a {$score}% AI Match score.");
    }

    public function withdraw(JobApplication $application): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('job-seeker'), 403);
        abort_unless($application->candidate_id === auth()->id(), 403);

        $application->update(['status' => 'Withdrawn', 'last_activity_at' => now()]);
        return back()->with('success', 'Application withdrawn.');
    }
}