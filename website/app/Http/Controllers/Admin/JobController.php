<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JobController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'all-jobs';
        $query = Job::with(['company', 'jobCategory'])->withCount('applications')->latest();
        $this->applyViewFilter($query, $view);
        if ($request->filled('search')) { $search = trim((string) $request->string('search')); $query->where(fn ($builder) => $builder->where('title', 'like', "%{$search}%")->orWhereHas('company', fn ($company) => $company->where('name', 'like', "%{$search}%"))); }
        return view('admin.jobs.index', ['jobs' => $query->paginate(20)->withQueryString(), 'view' => $view, 'filters' => $request->only(['search', 'view'])]);
    }

    private function applyViewFilter($query, string $view): void
    {
        match ($view) {
            'pending-approval' => $query->where('status', 'draft'),
            'approved-jobs', 'active-jobs' => $query->where('status', 'published')->where(function ($builder): void { $builder->whereNull('closing_date')->orWhere('closing_date', '>=', today()); }),
            'expired-jobs' => $query->where(function ($builder): void { $builder->where('closing_date', '<', today())->orWhere('status', 'expired'); }),
            'rejected-jobs' => $query->where('status', 'rejected'),
            'featured-jobs' => $query->where('is_featured', true),
            'urgent-jobs' => $query->where('is_urgent', true),
            'sponsored-jobs' => $query->where('is_sponsored', true),
            'apply-soon-jobs' => $query->whereBetween('closing_date', [today(), today()->addDays(7)]),
            'paid-apply-jobs' => $query->where('is_paid_apply', true),
            'external-jobs' => $query->where('is_external', true),
            'archived-jobs' => $query->where(fn ($builder) => $builder->where('status', 'archived')->orWhereNotNull('archived_at')),
            default => null,
        };
    }

    public function edit(Job $job): View
    {
        $this->ensureAdmin();
        return view('admin.jobs.form', ['job' => $job, 'companies' => Company::orderBy('name')->get(), 'categories' => JobCategory::orderBy('name')->get()]);
    }

    public function create(): View
    {
        $this->ensureAdmin();
        return view('admin.jobs.form', ['job' => new Job(['status' => 'draft', 'currency_code' => 'SGD', 'work_mode' => 'on-site', 'job_type' => 'full-time', 'vacancies' => 1, 'salary_visible' => true]), 'companies' => Company::orderBy('name')->get(), 'categories' => JobCategory::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $this->validated($request);
        $data['published_at'] = $data['status'] === 'published' ? now() : null;
        $data['archived_at'] = $data['status'] === 'archived' ? now() : null;
        if ($request->hasFile('image')) { $data['image_path'] = $this->storeImage($request); unset($data['image']); }
        Job::create($data);
        return redirect()->route('admin.jobs.index')->with('success', 'Job created.');
    }

    public function update(Request $request, Job $job): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $this->validated($request);
        if ($request->hasFile('image')) { $data['image_path'] = $this->storeImage($request); unset($data['image']); }
        $job->update($data + ['published_at' => $data['status'] === 'published' ? ($job->published_at ?: now()) : $job->published_at, 'archived_at' => $data['status'] === 'archived' ? ($job->archived_at ?: now()) : null]);
        return redirect()->route('admin.jobs.index', ['view' => $request->input('return_view', 'all-jobs')])->with('success', 'Job updated.');
    }

    public function status(Request $request, Job $job, string $status): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless(in_array($status, ['draft', 'published', 'rejected', 'expired', 'archived'], true), 404);
        $job->update(['status' => $status, 'published_at' => $status === 'published' ? ($job->published_at ?: now()) : $job->published_at, 'archived_at' => $status === 'archived' ? ($job->archived_at ?: now()) : null]);
        return back()->with('success', 'Job status updated.');
    }

    public function flag(Request $request, Job $job, string $flag): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless(in_array($flag, ['is_featured', 'is_urgent', 'is_sponsored', 'is_paid_apply', 'is_external'], true), 404);
        $job->update([$flag => ! $job->{$flag}]);
        return back()->with('success', 'Job flag updated.');
    }

    public function destroy(Job $job): RedirectResponse
    {
        $this->ensureAdmin(); $job->delete(); return back()->with('success', 'Job deleted.');
    }

    private function validated(Request $request): array
    {
        $rules = [
            'company_id' => ['required', 'exists:companies,id'],
            'job_category_id' => ['nullable', 'exists:job_categories,id'],
            'title' => ['required', 'string', 'max:180'],
            'image' => ['nullable', 'image', 'max:4096'],
            'description' => ['required', 'string'],
            'country_code' => ['required', 'string', 'max:3'],
            'location' => ['nullable', 'string', 'max:180'],
            'work_mode' => ['required', 'string', 'max:50'],
            'job_type' => ['required', 'string', 'max:50'],
            'experience_min' => ['nullable', 'integer', 'min:0'],
            'experience_max' => ['nullable', 'integer', 'min:0'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'size:3'],
            'vacancies' => ['required', 'integer', 'min:1'],
            'closing_date' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,published,rejected,expired,archived'],
            'application_fee' => ['nullable', 'numeric', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_urgent' => ['nullable', 'boolean'],
            'is_sponsored' => ['nullable', 'boolean'],
            'is_paid_apply' => ['nullable', 'boolean'],
            'is_external' => ['nullable', 'boolean'],
            'salary_visible' => ['nullable', 'boolean'],
        ];
        return $request->validate($rules) + [
            'salary_visible' => $request->boolean('salary_visible'),
            'is_featured' => $request->boolean('is_featured'),
            'is_urgent' => $request->boolean('is_urgent'),
            'is_sponsored' => $request->boolean('is_sponsored'),
            'is_paid_apply' => $request->boolean('is_paid_apply'),
            'is_external' => $request->boolean('is_external'),
        ];
    }

    private function storeImage(Request $request): string
    {
        $file = $request->file('image'); $directory = public_path('uploads/jobs');
        if (! is_dir($directory)) mkdir($directory, 0755, true);
        $name = 'job-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$file->extension();
        $file->move($directory, $name);
        return 'uploads/jobs/'.$name;
    }
}
