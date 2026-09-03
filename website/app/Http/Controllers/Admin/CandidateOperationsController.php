<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateNote;
use App\Models\CandidateResume;
use App\Models\CandidateSkill;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateOperationsController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    private function candidates(Request $request)
    {
        $query = User::whereHas('roles', fn ($builder) => $builder->where('slug', 'job-seeker'))->with('candidateProfile')->latest();
        $view = $request->string('view')->toString();
        if ($view === 'new-registrations') $query->where('created_at', '>=', now()->subDays(30));
        if ($view === 'verified-candidates') $query->whereNotNull('email_verified_at');
        if ($view === 'incomplete-profiles') $query->whereHas('candidateProfile', fn ($builder) => $builder->where('profile_completion', '<', 100));
        if ($view === 'complete-profiles') $query->whereHas('candidateProfile', fn ($builder) => $builder->where('profile_completion', '>=', 100));
        if ($view === 'blocked-candidates') $query->where('is_active', false);
        if ($request->filled('search')) { $search = trim((string) $request->string('search')); $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")); }
        return $query->paginate(20)->withQueryString();
    }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'all-job-seekers';
        $data = match ($view) {
            'candidate-resumes' => ['title' => 'Candidate Resumes', 'records' => CandidateResume::with('candidate')->latest()->paginate(20)->withQueryString()],
            'candidate-skills' => ['title' => 'Candidate Skills', 'records' => CandidateSkill::with('candidate')->latest()->paginate(20)->withQueryString()],
            'candidate-applications' => ['title' => 'Candidate Applications', 'records' => JobApplication::with(['candidate', 'job'])->latest()->paginate(20)->withQueryString()],
            'candidate-purchases' => ['title' => 'Candidate Purchases', 'records' => Payment::with('user')->whereNotNull('user_id')->whereIn('purpose', ['candidate', 'job_application', 'add-on'])->latest()->paginate(20)->withQueryString()],
            'candidate-login-history' => ['title' => 'Candidate Login History', 'records' => SecurityLog::with('user')->whereHas('user.roles', fn ($builder) => $builder->where('slug', 'job-seeker'))->latest()->paginate(30)->withQueryString()],
            'candidate-notes' => ['title' => 'Candidate Notes', 'records' => CandidateNote::with(['application.candidate', 'author'])->latest()->paginate(20)->withQueryString()],
            default => ['title' => str($view)->headline(), 'records' => $this->candidates($request)],
        };
        return view('admin.candidates.index', $data + ['view' => $view, 'candidates' => User::whereHas('roles', fn ($builder) => $builder->where('slug', 'job-seeker'))->orderBy('name')->get(), 'applications' => JobApplication::with('candidate')->latest()->get(), 'filters' => $request->only(['search', 'view'])]);
    }

    public function updateCandidate(Request $request, User $candidate): RedirectResponse
    {
        $this->ensureAdmin(); abort_unless($candidate->hasRole('job-seeker'), 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'unique:users,email,'.$candidate->id], 'phone' => ['nullable', 'string', 'max:32'], 'country_code' => ['nullable', 'string', 'max:3'], 'profile_completion' => ['required', 'integer', 'min:0', 'max:100']]);
        $candidate->update(collect($data)->except('profile_completion')->all());
        $candidate->candidateProfile()->updateOrCreate([], ['profile_completion' => $data['profile_completion'], 'country_code' => $data['country_code'] ?? null]);
        return back()->with('success', 'Candidate updated.');
    }

    public function toggleCandidate(User $candidate): RedirectResponse
    {
        $this->ensureAdmin(); abort_unless($candidate->hasRole('job-seeker'), 404); $candidate->update(['is_active' => ! $candidate->is_active]);
        return back()->with('success', 'Candidate access updated.');
    }

    public function destroyCandidate(User $candidate): RedirectResponse
    {
        $this->ensureAdmin(); abort_unless($candidate->hasRole('job-seeker'), 404); $candidate->delete();
        return back()->with('success', 'Candidate deleted.');
    }

    public function storeSkill(Request $request): RedirectResponse
    {
        $this->ensureAdmin(); CandidateSkill::create($request->validate(['candidate_id' => ['required', 'exists:users,id'], 'name' => ['required', 'string', 'max:120'], 'level' => ['nullable', 'string', 'max:50']]));
        return back()->with('success', 'Candidate skill added.');
    }

    public function destroySkill(CandidateSkill $skill): RedirectResponse { $this->ensureAdmin(); $skill->delete(); return back()->with('success', 'Candidate skill deleted.'); }

    public function updateApplication(Request $request, JobApplication $application): RedirectResponse
    {
        $this->ensureAdmin(); $application->update($request->validate(['status' => ['required', 'string', 'max:80']])); return back()->with('success', 'Application status updated.');
    }

    public function updatePurchase(Request $request, Payment $payment): RedirectResponse
    {
        $this->ensureAdmin(); $payment->update($request->validate(['status' => ['required', 'in:pending,paid,failed,refunded']])); return back()->with('success', 'Candidate purchase updated.');
    }

    public function storeNote(Request $request): RedirectResponse
    {
        $this->ensureAdmin(); $data = $request->validate(['job_application_id' => ['required', 'exists:job_applications,id'], 'note' => ['required', 'string', 'max:10000']]); $application = JobApplication::findOrFail($data['job_application_id']); CandidateNote::create($data + ['company_id' => $application->job->company_id, 'user_id' => auth()->id()]);
        return back()->with('success', 'Candidate note added.');
    }

    public function updateNote(Request $request, CandidateNote $note): RedirectResponse { $this->ensureAdmin(); $note->update($request->validate(['note' => ['required', 'string', 'max:10000']])); return back()->with('success', 'Candidate note updated.'); }
    public function destroyNote(CandidateNote $note): RedirectResponse { $this->ensureAdmin(); $note->delete(); return back()->with('success', 'Candidate note deleted.'); }
    public function destroyResume(CandidateResume $resume): RedirectResponse { $this->ensureAdmin(); $resume->delete(); return back()->with('success', 'Candidate resume deleted.'); }
}
