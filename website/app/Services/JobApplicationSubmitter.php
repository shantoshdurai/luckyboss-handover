<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;

/**
 * Creates one application, meters it, and notifies both sides.
 *
 * This used to be a private method on Seeker\DashboardController, shared by
 * apply() and applyAll(). Auto-apply is a third caller and runs from a console
 * command with no request and no session, so it lives here now. The point of
 * one class is that **an application is identical whoever asked for it** —
 * same score, same metering, same employer alert, same audit trail. The moment
 * auto-apply gets its own copy of this logic is the moment the two drift and
 * an auto-application starts arriving at an employer looking different from a
 * hand-made one.
 *
 * `$source` is the only thing a caller varies, and it is not cosmetic: it is
 * what lets a candidate see on their own applications list that we sent this
 * one for them, and withdraw it.
 */
class JobApplicationSubmitter
{
    public function __construct(
        private JobMatchService $matcher,
        private SubscriptionEntitlementService $entitlements,
        private NotificationService $notifications,
    ) {
    }

    public function submit(Job $job, User $user, string $source = 'Direct Candidate Portal'): JobApplication
    {
        // Null when we cannot honestly score this pairing. It is stored as null
        // and rendered as "not scored", not as a number: the fallback here used
        // to be `?? 88`, so a candidate we knew nothing about was announced to
        // the employer as an 88% match, and told so themselves in the
        // confirmation message.
        $score = $this->matcher->score($job, $user)['score'] ?? null;
        $matchNote = $score === null ? '' : " ({$score}% match)";

        $application = JobApplication::firstOrCreate(
            ['job_id' => $job->id, 'candidate_id' => $user->id],
            [
                'status' => 'New',
                'match_score' => $score,
                'applied_at' => now(),
                'last_activity_at' => now(),
                'source' => $source,
            ]
        );

        // Metered, not charged. `apply` is a zero-priced, unlimited SKU: the
        // candidate is never refused and never sees a balance. It is recorded
        // because sir's decision on 2026-09-07 was that seekers are free "in a
        // way like zero rupees", flippable to paid from the backend later — and
        // that decision is only worth anything if the usage history starts
        // accruing now. `wasRecentlyCreated` keeps a repeat tap from
        // double-counting an application that already existed.
        if ($application->wasRecentlyCreated) {
            $this->entitlements->consume($user, 'apply', 1, $application);
        }

        try {
            $this->notifications->send(
                $user,
                'application_status',
                "Application Submitted: {$job->title}",
                "Your application for {$job->title} at ".($job->company->name ?? 'Verified Employer')." has been received{$matchNote}.",
                ['job_id' => $job->id, 'application_id' => $application->id],
                'job_match'
            );
        } catch (\Throwable $e) {
        }

        $employerUser = $job->company?->users()->first();
        if ($employerUser) {
            try {
                $this->notifications->send(
                    $employerUser,
                    'applicant_alert',
                    "New Application: {$user->name}",
                    "{$user->name} applied for {$job->title}{$matchNote}",
                    ['job_id' => $job->id, 'application_id' => $application->id],
                    'new_candidate'
                );
            } catch (\Throwable $e) {
            }
        }

        return $application;
    }
}
