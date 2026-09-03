<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Country;
use App\Models\Job;
use App\Models\JobCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicPortalController extends Controller
{
    public function jobs(Request $request): View
    {
        $jobs = Job::with('company', 'jobCategory')
            ->where('status', 'published')
            ->when($request->filled('keyword'), fn ($query) => $query->where('title', 'like', '%'.$request->string('keyword').'%'))
            ->when($request->filled('country'), fn ($query) => $query->where('country_code', $request->string('country')))
            ->when($request->filled('location'), fn ($query) => $query->where('location', 'like', '%'.$request->string('location').'%'))
            ->when($request->filled('category'), fn ($query) => $query->where('job_category_id', $request->integer('category')))
            ->when($request->filled('work_mode'), fn ($query) => $query->where('work_mode', $request->string('work_mode')))
            ->when($request->filled('job_type'), fn ($query) => $query->where('job_type', $request->string('job_type')))
            ->when($request->filled('min_salary'), fn ($query) => $query->where('salary_min', '>=', $request->numeric('min_salary')))
            ->when($request->filled('max_salary'), fn ($query) => $query->where('salary_max', '<=', $request->numeric('max_salary')))
            ->when($request->filled('experience'), function ($query) use ($request) {
                $exp = $request->string('experience')->toString();
                if ($exp === 'entry') $query->where('experience_min', '<=', 2);
                elseif ($exp === 'mid') $query->whereBetween('experience_min', [2, 5]);
                elseif ($exp === 'senior') $query->where('experience_min', '>=', 5);
            })
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $user = auth()->user();
        $savedJobIds = $user ? $user->savedJobs()->pluck('job_id')->all() : [];
        $appliedJobIds = ($user && $user->hasRole('job-seeker')) ? $user->applications()->where('status', '!=', 'Withdrawn')->pluck('job_id')->all() : [];

        return view('public.jobs', [
            'jobs' => $jobs,
            'countries' => Country::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'categories' => JobCategory::where('is_active', true)->orderBy('name')->get(),
            'savedJobIds' => $savedJobIds,
            'appliedJobIds' => $appliedJobIds,
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());
        abort_if(mb_strlen($term) < 2, 400, 'Enter at least two characters.');

        $field = $request->string('field')->toString() === 'location' ? 'location' : 'title';
        return response()->json(Job::where('status', 'published')->whereNotNull($field)->where($field, 'like', '%'.$term.'%')->orderBy($field)->limit(8)->pluck($field)->unique()->values());
    }

    public function show(Job $job): View
    {
        abort_unless($job->status === 'published', 404);

        $application = auth()->check() && auth()->user()->hasRole('job-seeker')
            ? auth()->user()->applications()->where('job_id', $job->id)->where('status', '!=', 'Withdrawn')->latest()->first()
            : null;
        $matchScore = auth()->check() && auth()->user()->hasRole('job-seeker')
            ? (int) ($application?->match_score ?? app(\App\Services\AIRecruitmentEngineService::class)->calculateMatch($job, auth()->user())['score'] ?? 0)
            : null;

        return view('public.job-detail', [
            'job' => $job->load('company', 'jobCategory'),
            'application' => $application,
            'matchScore' => $matchScore,
        ]);
    }

    public function categories(): View { return view('public.categories', ['categories' => JobCategory::with('jobs')->where('is_active', true)->orderBy('sort_order')->get()]); }
    public function specializations(): View { return view('public.specializations', ['categories' => JobCategory::where('is_active', true)->orderBy('sort_order')->get(), 'companies' => Company::where('status', 'verified')->take(8)->get()]); }
    public function employers(): View { return view('public.employers', ['companies' => Company::where('status', 'verified')->take(12)->get(), 'packages' => \App\Models\Package::where('is_active', true)->get()]); }
    public function seekers(): View { return view('public.seekers'); }
    public function contact(\App\Services\SiteSettingsService $settings): View { return view('public.contact', ['contact' => $settings->contact()]); }
}
