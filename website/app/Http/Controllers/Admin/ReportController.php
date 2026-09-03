<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Interview;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'employer-reports';
        [$metrics, $rows] = match ($view) {
            'employer-reports' => [['Companies' => Company::count(), 'Verified' => Company::where('status', 'verified')->count(), 'Pending' => Company::where('status', 'pending')->count()], Company::latest()->take(20)->get()],
            'job-seeker-reports' => [['Candidates' => User::whereHas('roles', fn ($q) => $q->where('slug', 'job-seeker'))->count(), 'Active' => User::whereHas('roles', fn ($q) => $q->where('slug', 'job-seeker'))->where('is_active', true)->count()], User::whereHas('roles', fn ($q) => $q->where('slug', 'job-seeker'))->latest()->take(20)->get()],
            'job-reports' => [['Jobs' => Job::count(), 'Published' => Job::where('status', 'published')->count(), 'Expired' => Job::where('status', 'expired')->count()], Job::with('company')->latest()->take(20)->get()],
            'application-reports' => [['Applications' => JobApplication::count(), 'Shortlisted' => JobApplication::where('status', 'Shortlisted')->count(), 'Joined' => JobApplication::where('status', 'Joined')->count()], JobApplication::with(['candidate', 'job'])->latest()->take(20)->get()],
            'interview-reports' => [['Interviews' => Interview::count(), 'Completed' => Interview::where('status', 'completed')->count(), 'Upcoming' => Interview::where('scheduled_at', '>', now())->count()], Interview::latest()->take(20)->get()],
            'offer-reports', 'hiring-reports' => [['Offers' => Offer::count(), 'Sent' => Offer::where('status', 'sent')->count(), 'Accepted' => Offer::where('status', 'accepted')->count()], Offer::latest()->take(20)->get()],
            'subscription-reports' => [['Subscriptions' => Subscription::count(), 'Active' => Subscription::where('status', 'active')->count(), 'Expired' => Subscription::where('status', 'expired')->count()], Subscription::with(['company', 'package'])->latest()->take(20)->get()],
            'payment-reports', 'revenue-reports' => [['Transactions' => Payment::count(), 'Paid' => Payment::where('status', 'paid')->count(), 'Revenue' => Payment::where('status', 'paid')->sum('amount')], Payment::latest()->take(20)->get()],
            'ai-usage-reports', 'api-cost-reports' => [['Integrations' => \App\Models\ApiIntegration::count(), 'Requests' => \App\Models\ApiIntegration::sum('usage_count'), 'Errors' => \App\Models\ApiIntegration::whereNotNull('last_error')->count()], \App\Models\ApiIntegration::latest()->get()],
            'candidate-source-reports' => [['Direct' => JobApplication::where('source', 'Direct Applicant')->count(), 'Other sources' => JobApplication::where('source', '!=', 'Direct Applicant')->count()], JobApplication::select('source')->selectRaw('count(*) as total')->groupBy('source')->get()],
            'external-data-reports' => [['Jobs' => Job::where('is_external', true)->count(), 'Import batches' => \App\Models\ImportBatch::count(), 'Failed records' => \App\Models\ImportBatch::sum('records_failed')], \App\Models\ImportBatch::with('externalSource')->latest()->take(20)->get()],
            'country-reports' => [['Companies' => Company::distinct('country_code')->count('country_code'), 'Jobs' => Job::distinct('country_code')->count('country_code')], Company::select('country_code')->selectRaw('count(*) as total')->groupBy('country_code')->get()],
            'category-reports' => [['Categories' => \App\Models\JobCategory::count(), 'Jobs' => Job::count()], \App\Models\JobCategory::withCount('jobs')->orderByDesc('jobs_count')->get()],
            default => [['Records' => 0], collect()],
        };
        return view('admin.reports.index', compact('view', 'metrics', 'rows'));
    }
}
