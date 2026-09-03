<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            // What the search box cycles through. Taken from the vacancies that
            // actually exist, so the hint can never advertise a trade we have
            // nothing in — a candidate typing back a suggestion and getting an
            // empty result is worse than no suggestion at all.
            'rollingTerms' => Cache::remember('home.rolling_terms', 600, function () {
                $titles = Job::where('status', 'published')
                    ->orderByDesc('published_at')
                    ->pluck('title')
                    ->unique()
                    ->take(6)
                    ->values()
                    ->all();

                return $titles ?: ['Warehouse Supervisor', 'Forklift Driver', 'Site Supervisor'];
            }),

            'stats' => Cache::remember('home.stats', 900, function () {
                return [
                    'activeJobs' => Job::where('status', 'published')->count(),
                    'jobSeekers' => User::whereHas('roles', fn ($q) => $q->where('slug', 'job-seeker'))->count(),
                    'employers'  => Company::where('status', 'verified')->count(),
                ];
            }),

            'featuredJobs' => Cache::remember('home.featured_jobs', 300, function () {
                // Ensure a rich mix of Singapore (SGD) and India (INR) featured opportunities
                $inrJobs = Job::with('company')
                    ->where('status', 'published')
                    ->where('currency_code', 'INR')
                    ->latest('published_at')
                    ->take(3)
                    ->get();

                $sgdJobs = Job::with('company')
                    ->where('status', 'published')
                    ->where('currency_code', '!=', 'INR')
                    ->latest('published_at')
                    ->take(3)
                    ->get();

                return $inrJobs->concat($sgdJobs)->shuffle();
            }),

            'categories' => Cache::remember('home.categories', 600, function () {
                return JobCategory::withCount(['jobs' => fn ($q) => $q->where('status', 'published')])
                    ->where('show_on_home', true)
                    ->orderBy('sort_order')
                    ->take(8)
                    ->get();
            }),

            'blogs' => Cache::remember('home.blogs', 1800, function () {
                return Blog::where('is_published', true)
                    ->latest('published_at')
                    ->take(3)
                    ->get();
            }),

            'slider' => Cache::remember('home.slider', 600, function () {
                return Slider::where('is_active', true)
                    ->where('web_enabled', true)
                    ->orderBy('sort_order')
                    ->first();
            }),

            'savedJobIds' => auth()->check() ? auth()->user()->savedJobs()->pluck('job_id')->all() : [],
            'appliedJobIds' => (auth()->check() && auth()->user()->hasRole('job-seeker')) ? auth()->user()->applications()->pluck('job_id')->all() : [],
        ]);
    }
}