<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecruitmentController extends Controller
{
    private const STATUSES = ['New', 'Viewed', 'Contacted', 'Shortlisted', 'Interview Scheduled', 'Interviewed', 'Assessment', 'Selected', 'Offer Prepared', 'Offer Sent', 'Offer Accepted', 'Joined', 'Rejected', 'Archived'];

    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'all-applications';
        $query = JobApplication::with(['candidate', 'job.company', 'job.jobCategory'])->latest('applied_at');
        $status = $this->statusForView($view);
        if ($status) $query->where('status', $status);
        if ($request->filled('search')) { $search = trim((string) $request->string('search')); $query->where(fn ($builder) => $builder->whereHas('candidate', fn ($candidate) => $candidate->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))->orWhereHas('job', fn ($job) => $job->where('title', 'like', "%{$search}%"))); }
        return view('admin.recruitment.index', ['applications' => $query->paginate(20)->withQueryString(), 'view' => $view, 'statuses' => self::STATUSES, 'filters' => $request->only(['search', 'view'])]);
    }

    private function statusForView(string $view): ?string
    {
        return match ($view) {
            'new-applications' => 'New', 'shortlisted' => 'Shortlisted', 'contacted' => 'Contacted', 'interview-scheduled' => 'Interview Scheduled', 'interviewed' => 'Interviewed', 'assessment' => 'Assessment', 'selected' => 'Selected', 'offer-prepared' => 'Offer Prepared', 'offer-sent' => 'Offer Sent', 'offer-accepted' => 'Offer Accepted', 'joined' => 'Joined', 'rejected' => 'Rejected', 'archived-candidates' => 'Archived', default => null,
        };
    }

    public function status(Request $request, JobApplication $application): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', self::STATUSES)], 'remark' => ['nullable', 'string', 'max:1000']]);
        $old = $application->status;
        $application->update(['status' => $data['status'], 'last_activity_at' => now()]);
        $application->statusHistories()->create(['user_id' => auth()->id(), 'from_status' => $old, 'to_status' => $data['status'], 'remark' => $data['remark'] ?? null]);
        return back()->with('success', 'Application status updated.');
    }

    public function scheduleInterview(Request $request, JobApplication $application): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['scheduled_at' => ['required', 'date'], 'mode' => ['required', 'string', 'max:80'], 'duration_minutes' => ['required', 'integer', 'min:15'], 'venue' => ['nullable', 'string', 'max:255'], 'meeting_link' => ['nullable', 'url', 'max:255'], 'notes' => ['nullable', 'string']]);
        Interview::create($data + ['job_application_id' => $application->id, 'company_id' => $application->job->company_id, 'interviewer_id' => auth()->id(), 'time_zone' => 'Asia/Singapore', 'status' => 'scheduled']);
        $this->setStatus($application, 'Interview Scheduled', 'Interview scheduled by admin.');
        return back()->with('success', 'Interview scheduled.');
    }

    public function createOffer(Request $request, JobApplication $application): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['salary' => ['required', 'numeric', 'min:0'], 'currency_code' => ['required', 'string', 'size:3'], 'joining_date' => ['nullable', 'date'], 'work_location' => ['nullable', 'string', 'max:255'], 'terms' => ['nullable', 'string']]);
        Offer::create($data + ['job_application_id' => $application->id, 'company_id' => $application->job->company_id, 'position' => $application->job->title, 'status' => 'sent', 'sent_at' => now()]);
        $this->setStatus($application, 'Offer Sent', 'Offer created by admin.');
        return back()->with('success', 'Offer created and sent.');
    }

    public function destroy(JobApplication $application): RedirectResponse
    {
        $this->ensureAdmin(); $application->delete(); return back()->with('success', 'Application deleted.');
    }

    private function setStatus(JobApplication $application, string $status, string $remark): void
    {
        $old = $application->status; $application->update(['status' => $status, 'last_activity_at' => now()]); $application->statusHistories()->create(['user_id' => auth()->id(), 'from_status' => $old, 'to_status' => $status, 'remark' => $remark]);
    }
}
