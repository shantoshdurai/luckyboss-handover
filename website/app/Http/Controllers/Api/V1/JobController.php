<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Services\JobViewRecorder;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = Job::with(['company', 'jobCategory'])->where('status', 'published')
            ->when($request->string('keyword')->toString(), fn ($query, $keyword) => $query->where('title', 'like', "%{$keyword}%"))
            ->when($request->string('country')->toString(), fn ($query, $country) => $query->where('country_code', $country))
            ->latest('published_at')->paginate(15);

        return JobResource::collection($jobs);
    }

    public function show(Job $job, Request $request, JobViewRecorder $views): JobResource
    {
        abort_unless($job->status === 'published', 404);

        // Recorded here rather than in the app so a view counts once, from one
        // place, whether it came from the mobile app or the public site.
        $views->record($job, $request, 'app');

        return new JobResource($job->load(['company', 'jobCategory']));
    }
}