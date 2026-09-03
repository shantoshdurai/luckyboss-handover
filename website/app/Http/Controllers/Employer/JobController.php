<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Company;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobController extends Controller
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

    public function index(Request $request)
    {
        $status = $request->string('status')->toString();
        $jobs = $this->company()->jobs()->withCount('applications')
            ->when($status === 'active', fn ($query) => $query->where('status', 'published')->where(fn ($query) => $query->whereNull('closing_date')->orWhere('closing_date', '>=', today())))
            ->when($status === 'expired', fn ($query) => $query->where(fn ($query) => $query->where('status', 'expired')->orWhere('closing_date', '<', today())))
            ->when($status === 'featured', fn ($query) => $query->where('is_featured', true))
            ->when($status === 'archived', fn ($query) => $query->where(fn ($query) => $query->where('status', 'archived')->orWhereNotNull('archived_at')))
            ->when($status === 'draft', fn ($query) => $query->where('status', 'draft'))
            ->latest()
            ->paginate(20)->withQueryString();
        return view('employer.jobs.index', ['jobs' => $jobs, 'statusFilter' => $status]);
    }

    public function create()
    {
        return view('employer.jobs.form', ['job' => null, 'categories' => JobCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(), 'countries' => Country::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $data['company_id'] = $this->company()->id;
        $data['status'] = $request->boolean('publish_now') ? 'published' : 'draft';
        $data['published_at'] = $data['status'] === 'published' ? now() : null;
        $this->storeImage($request, $data);
        Job::create($data);
        return redirect()->route('employer.jobs.index')->with('success', 'Job saved.');
    }

    public function edit(Job $job)
    {
        abort_unless($job->company_id === $this->company()->id || auth()->user()?->hasRole('super-admin'), 404);
        return view('employer.jobs.form', ['job' => $job, 'categories' => JobCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(), 'countries' => Country::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function update(Request $request, Job $job)
    {
        abort_unless($job->company_id === $this->company()->id || auth()->user()?->hasRole('super-admin'), 404);
        $data = $this->data($request);
        $this->storeImage($request, $data);
        $job->update($data);
        return redirect()->route('employer.jobs.index')->with('success', 'Job updated.');
    }

    public function destroy(Job $job)
    {
        abort_unless($job->company_id === $this->company()->id || auth()->user()?->hasRole('super-admin'), 404);
        $job->delete();
        return redirect()->route('employer.jobs.index')->with('success', 'Job deleted.');
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'job_category_id' => ['required', 'exists:job_categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'country_code' => ['required', 'exists:countries,code'],
            'location' => ['required', 'string', 'max:200'],
            'job_type' => ['required', 'string'],
            'work_mode' => ['required', 'string'],
            'openings' => ['required', 'integer', 'min:1'],
            'salary_min' => ['nullable', 'numeric'],
            'salary_max' => ['nullable', 'numeric'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'salary_visible' => ['nullable', 'boolean'],
            'experience_min' => ['nullable', 'integer'],
            'experience_max' => ['nullable', 'integer'],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
            'is_urgent' => ['nullable', 'boolean'],
            'closing_date' => ['nullable', 'date'],
        ]);
    }

    private function storeImage(Request $request, array &$data): void
    {
        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            $dir = public_path('uploads/jobs');
            if (! is_dir($dir)) mkdir($dir, 0755, true);
            $name = 'job-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$file->extension();
            $file->move($dir, $name);
            $data['featured_image_path'] = 'uploads/jobs/'.$name;
        }
    }
}