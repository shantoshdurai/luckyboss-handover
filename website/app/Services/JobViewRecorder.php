<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Counts how often a vacancy is actually seen.
 *
 * This exists because the employer app sells a boost and then had nothing true
 * to report about it. The alternative was a screen of invented numbers, which
 * for a paid feature is not a shortcut but a lie.
 *
 * ONE VIEW PER PERSON PER JOB PER DAY. A candidate who opens a vacancy, backs
 * out to compare it with another and opens it again has looked once as far as
 * the employer is concerned. Counting raw hits would let a boost look four
 * times more effective than it was, and the first employer to check against
 * their applicant count would stop believing any number we show them.
 *
 * The de-duplication key is hashed. For a signed-out visitor it is derived from
 * the IP address, which is personal data we have no reason to keep in readable
 * form once it has done its only job.
 */
class JobViewRecorder
{
    public function record(Job $job, Request $request, string $source = 'web'): void
    {
        try {
            $viewer = $request->user();

            // An employer looking at their own vacancy is not an impression.
            if ($viewer !== null && $viewer->companies()->where('companies.id', $job->company_id)->exists()) {
                return;
            }

            $identity = $viewer?->id !== null
                ? 'u:'.$viewer->id
                : 'ip:'.$request->ip();

            $hash = hash('sha256', $identity.'|'.$job->id.'|'.now()->toDateString());

            // firstOrCreate rather than an existence check: two taps arriving at
            // once would both pass the check and then collide on the unique
            // index. Letting the database arbitrate is the only version without
            // a race.
            JobView::firstOrCreate(
                ['job_id' => $job->id, 'dedupe_hash' => $hash],
                [
                    'viewer_id' => $viewer?->id,
                    'source' => $source,
                    'viewed_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // Never let analytics break the page a candidate is trying to read.
            Log::warning('[JobView] '.$e->getMessage());
        }
    }
}
