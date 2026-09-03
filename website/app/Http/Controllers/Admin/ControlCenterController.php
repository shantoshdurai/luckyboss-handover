<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRecord;
use App\Models\ApiIntegration;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControlCenterController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $section = $request->string('section')->toString(); $view = $request->string('view')->toString();
        $data = match ($section) {
            'analytics' => $this->analytics($view),
            'users-permissions' => $this->users($view),
            'audit-logs' => $this->audit($view),
            default => ['records' => AdminRecord::where('module', "{$section}-{$view}")->latest()->get(), 'metrics' => []],
        };
        return view('admin.control-center.index', compact('section', 'view', 'data'));
    }

    private function analytics(string $view): array
    {
        return match ($view) {
            'hiring-funnel' => ['metrics' => ['Applications' => JobApplication::count(), 'Interviewed' => JobApplication::whereIn('status', ['Interviewed', 'Selected', 'Offer Sent', 'Joined'])->count(), 'Joined' => JobApplication::where('status', 'Joined')->count()]],
            'application-conversion' => ['metrics' => ['Applications' => JobApplication::count(), 'Shortlisted' => JobApplication::whereIn('status', ['Shortlisted', 'Interview Scheduled', 'Interviewed', 'Selected', 'Offer Sent', 'Joined'])->count(), 'Conversion %' => $this->percent(JobApplication::whereIn('status', ['Shortlisted', 'Interview Scheduled', 'Interviewed', 'Selected', 'Offer Sent', 'Joined'])->count(), JobApplication::count())]],
            'interview-conversion' => ['metrics' => ['Interviews' => \App\Models\Interview::count(), 'Completed' => \App\Models\Interview::where('status', 'completed')->count(), 'Completion %' => $this->percent(\App\Models\Interview::where('status', 'completed')->count(), \App\Models\Interview::count())]],
            'offer-acceptance-rate' => ['metrics' => ['Offers' => \App\Models\Offer::count(), 'Accepted' => \App\Models\Offer::where('status', 'accepted')->count(), 'Acceptance %' => $this->percent(\App\Models\Offer::where('status', 'accepted')->count(), \App\Models\Offer::count())]],
            'average-time-to-hire' => ['metrics' => ['Joined candidates' => JobApplication::where('status', 'Joined')->count(), 'Tracked days' => 0, 'Average days' => 'N/A']],
            'best-job-categories' => ['metrics' => ['Categories' => \App\Models\JobCategory::count(), 'Published jobs' => Job::where('status', 'published')->count()]],
            'employer-activity' => ['metrics' => ['Employers' => Company::count(), 'Jobs posted' => Job::count(), 'Applications' => JobApplication::count()]],
            'candidate-growth' => ['metrics' => ['Candidates' => User::whereHas('roles', fn ($q) => $q->where('slug', 'job-seeker'))->count(), 'New 30 days' => User::whereHas('roles', fn ($q) => $q->where('slug', 'job-seeker'))->where('created_at', '>=', now()->subDays(30))->count()]],
            'revenue-by-country' => ['metrics' => Company::select('country_code')->selectRaw('count(*) as total')->groupBy('country_code')->pluck('total', 'country_code')->all()],
            'revenue-by-currency' => ['metrics' => Payment::where('status', 'paid')->select('currency_code')->selectRaw('sum(amount) as total')->groupBy('currency_code')->pluck('total', 'currency_code')->all()],
            'ai-usage-by-employer' => ['metrics' => ApiIntegration::select('name', 'usage_count')->pluck('usage_count', 'name')->all()],
            default => ['metrics' => []],
        };
    }

    private function users(string $view): array
    {
        return match ($view) {
            'admin-users' => ['users' => User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['super-admin', 'operations-admin', 'finance-admin', 'support-agent', 'api-manager']))->with('roles')->latest()->get()],
            'permissions' => ['records' => \App\Models\Permission::orderBy('module')->get()],
            'admin-roles' => ['records' => \App\Models\Role::withCount('users')->orderBy('name')->get()],
            default => ['records' => AdminRecord::where('module', "users-permissions-{$view}")->latest()->get()],
        };
    }

    private function audit(string $view): array
    {
        $query = AuditLog::with(['user', 'company'])->latest();
        if ($view === 'security-logs') return ['security' => SecurityLog::with('user')->latest()->paginate(30)];
        if ($view === 'employer-activity') $query->whereNotNull('company_id');
        if ($view === 'candidate-activity') $query->whereHas('user.roles', fn ($q) => $q->where('slug', 'job-seeker'));
        return ['audit' => $query->paginate(30)];
    }

    private function percent(int $part, int $whole): string { return $whole ? number_format($part / $whole * 100, 1).'%' : '0%'; }

    public function storeRecord(Request $request): RedirectResponse
    {
        $this->ensureAdmin(); $data = $request->validate(['section' => ['required', 'string'], 'view' => ['required', 'string'], 'name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string'], 'payload' => ['nullable', 'string']]);
        AdminRecord::create(['module' => "{$data['section']}-{$data['view']}", 'name' => $data['name'], 'slug' => str($data['name'])->slug(), 'description' => $data['description'] ?? null, 'payload' => filled($data['payload'] ?? null) ? json_decode($data['payload'], true) : [], 'is_active' => true]);
        return back()->with('success', 'Control record created.');
    }
}
