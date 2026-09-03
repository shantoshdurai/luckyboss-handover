<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedJobController extends Controller
{
    public function toggle(Request $request, Job $job): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('job-seeker'), 403);
        $saved = auth()->user()->savedJobs()->where('job_id', $job->id)->first();
        if ($saved) {
            $saved->delete();
            $message = 'Job removed from saved jobs.';
        } else {
            auth()->user()->savedJobs()->create(['job_id' => $job->id]);
            $message = 'Job saved to your bookmarks.';
        }
        return back()->with('success', $message);
    }
}
