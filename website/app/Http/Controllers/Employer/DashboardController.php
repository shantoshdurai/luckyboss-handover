<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Interview;
use App\Models\Offer;
use App\Models\Subscription;
use App\Models\JobApplication;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('employer')) {
            if ($user?->hasRole('super-admin')) {
                return redirect()->route('admin.dashboard')->with('info', 'Logged in as Administrator. Redirected to Admin Command Center.');
            }
            if ($user?->hasRole('job-seeker')) {
                return redirect()->route('seeker.dashboard')->with('info', 'Logged in as Job Seeker. Redirected to Candidate Dashboard.');
            }
            return redirect()->route('login')->with('info', 'Please sign in as an Employer to access this portal.');
        }

        $company = $user->companies()->first();
        if (!$company) {
            $company = Company::create([
                'name' => $user->name . ' Enterprise',
                'country_code' => $user->country_code ?? 'SGP',
                'status' => 'verified',
            ]);
            $company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);
        }
        $jobs = Job::where('company_id', $company->id)->withCount('applications')->latest()->get();
        $applications = JobApplication::whereHas('job', fn ($query) => $query->where('company_id', $company->id))->get();
        $interviews = Interview::where('company_id', $company->id)->where('scheduled_at', '>=', now())->latest('scheduled_at')->get();
        $offers = Offer::where('company_id', $company->id)->latest()->get();
        $subscription = $company->subscriptions()->with('package')->latest('expires_at')->first();
        return view('employer.dashboard', [
            'company' => $company,
            'jobs' => $jobs,
            'applications' => $applications->count(),
            'newApplicants' => $applications->where('status', 'New')->count(),
            'recommendedCandidates' => $applications->where('match_score', '>=', 70)->count(),
            'shortlisted' => $applications->where('status', 'Shortlisted')->count(),
            'interviews' => $interviews,
            'interviewsToday' => $interviews->filter(fn ($interview) => $interview->scheduled_at?->isToday())->count(),
            'offers' => $offers,
            'offersSent' => $offers->whereIn('status', ['sent', 'accepted', 'rejected'])->count(),
            'hired' => $applications->where('status', 'Joined')->count(),
            'subscription' => $subscription,
            'entitlements' => $subscription?->entitlements ?? $subscription?->package?->entitlements ?? [],
        ]);
    }
}