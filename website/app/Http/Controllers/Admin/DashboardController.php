<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FeatureFlag;
use App\Models\Job;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('super-admin')) {
            if ($user?->hasRole('employer')) {
                return redirect()->route('employer.dashboard')->with('error', 'Access to Administrator Panel is restricted.');
            }
            if ($user?->hasRole('job-seeker')) {
                return redirect()->route('seeker.dashboard')->with('error', 'Access to Administrator Panel is restricted.');
            }
            return redirect()->route('login')->with('info', 'Please sign in with administrator credentials.');
        }

        return view('admin.dashboard', [
            'metrics' => ['companies' => Company::count(), 'candidates' => User::whereHas('roles', fn ($query) => $query->where('slug', 'job-seeker'))->count(), 'jobs' => Job::where('status', 'published')->count(), 'features' => FeatureFlag::where('is_enabled', true)->count()],
            'features' => FeatureFlag::orderBy('name')->get(),
        ]);
    }
}