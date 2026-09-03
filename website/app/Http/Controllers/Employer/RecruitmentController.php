<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Offer;
use App\Models\Company;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class RecruitmentController extends Controller
{
    private function company(): Company
    {
        $user = auth()->user();
        if ($user) {
            $company = $user->companies()->first();
            if ($company) return $company;

            if ($user->hasRole('super-admin')) {
                return Company::first() ?: Company::create([
                    'name' => 'Luckyboss Global Recruitment',
                    'country_code' => 'SGP',
                    'status' => 'verified',
                ]);
            }

            if ($user->hasRole('employer')) {
                $company = Company::create([
                    'name' => ($user->name ?: 'Corporate') . ' Enterprise',
                    'country_code' => $user->country_code ?? 'SGP',
                    'status' => 'verified',
                ]);
                $company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);
                return $company;
            }
        }

        return Company::firstOrFail();
    }

    private function application(Job $job, JobApplication $application): JobApplication
    {
        abort_unless($job->company_id === $this->company()->id || auth()->user()?->hasRole('super-admin'), 404);
        return $application;
    }

    public function show(Job $job)
    {
        abort_unless($job->company_id === $this->company()->id || auth()->user()?->hasRole('super-admin'), 404);
        return view('employer.jobs.applicants', [
            'job' => $job,
            'applications' => $job->applications()->with('candidate.candidateProfile')->latest('applied_at')->get()
        ]);
    }

    public function status(Request $request, Job $job, JobApplication $application)
    {
        $application = $this->application($job, $application);
        $data = $request->validate([
            'status' => ['required', 'in:New,Viewed,Contacted,Shortlisted,Interview Scheduled,Interviewed,Selected,Offer Sent,Joined,Rejected,Archived'],
            'remark' => ['nullable', 'string', 'max:500']
        ]);
        $old = $application->status;
        $application->update(['status' => $data['status'], 'last_activity_at' => now()]);
        $application->statusHistories()->create([
            'user_id' => auth()->id(),
            'from_status' => $old,
            'to_status' => $data['status'],
            'remark' => $data['remark'] ?? null
        ]);
        app(NotificationService::class)->send($application->candidate, 'application_update', 'Application status updated', "Your application for {$job->title} is now {$data['status']}.", ['job_id' => $job->id], 'application_update');
        return back()->with('success', 'Candidate status updated.');
    }

    public function interview(Request $request, Job $job, JobApplication $application)
    {
        $application = $this->application($job, $application);
        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'mode' => ['required', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:15'],
            'venue' => ['nullable', 'string'],
            'meeting_link' => ['nullable', 'url'],
            'notes' => ['nullable', 'string']
        ]);
        Interview::create($data + [
            'job_application_id' => $application->id,
            'company_id' => $this->company()->id,
            'interviewer_id' => auth()->id(),
            'time_zone' => 'Asia/Singapore',
            'status' => 'scheduled'
        ]);
        $application->update(['status' => 'Interview Scheduled', 'last_activity_at' => now()]);
        app(NotificationService::class)->send($application->candidate, 'interview', 'Interview scheduled', "An interview has been scheduled for {$job->title}.", ['job_id' => $job->id], 'interview_alert');
        return back()->with('success', 'Interview scheduled.');
    }

    public function offer(Request $request, Job $job, JobApplication $application)
    {
        $application = $this->application($job, $application);
        $data = $request->validate([
            'salary' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'size:3'],
            'joining_date' => ['nullable', 'date'],
            'work_location' => ['nullable', 'string'],
            'terms' => ['nullable', 'string']
        ]);
        Offer::create($data + [
            'job_application_id' => $application->id,
            'company_id' => $this->company()->id,
            'position' => $job->title,
            'status' => 'sent',
            'sent_at' => now()
        ]);
        $application->update(['status' => 'Offer Sent', 'last_activity_at' => now()]);
        app(NotificationService::class)->send($application->candidate, 'offer', 'New offer received', "You have received an offer for {$job->title}.", ['job_id' => $job->id], 'offer_alert');
        return back()->with('success', 'Offer generated and sent.');
    }
}