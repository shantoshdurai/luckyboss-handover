<?php

namespace App\Services;

use App\Models\AutoApplyRun;
use App\Models\AutoApplySetting;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Applies to matching vacancies on a candidate's behalf while they are not
 * watching.
 *
 * This is the feature TickBig advertises as "it applies even while they sleep".
 * Ours differs from theirs in one respect that is not negotiable:
 *
 * **Every application stays inside the Lucky Boss job table.** We do not open,
 * scrape or submit forms on LinkedIn, Naukri, Shine, Indeed or Monster. That is
 * sir's own instruction — §69 of the functional specification, "do not design
 * the business model around unauthorized scraping" — and it is also the
 * practical call: submitting forms as the candidate breaches those sites'
 * terms and gets the candidate's account banned, not ours. If external
 * coverage is ever wanted, the route is a partner feed or an ATS integration
 * with a signed agreement, not a robot with their password.
 *
 * Four gates, and each one refuses rather than guesses:
 *
 *  1. The platform switch (admin). A kill-switch over everyone.
 *  2. The candidate's own opt-in. An administrator turning the feature on is
 *     not consent from any individual candidate.
 *  3. Match readiness. `JobMatchService` refuses to score a thin profile, and
 *     auto-apply refuses to send anything on a refusal. This is the whole
 *     reason the scorer was rewritten: the old path scored an empty profile at
 *     45% against every vacancy, and auto-applying on that would have carpeted
 *     employers with applications we had no basis for.
 *  4. The daily limit and the entitlement balance.
 *
 * Every run writes an AutoApplyRun row, including runs that send nothing, with
 * the reason. A candidate must always be able to see what we did for them.
 */
class AutoApplyService
{
    public function __construct(
        private SiteSettingsService $settings,
        private JobMatchService $matcher,
        private JobApplicationSubmitter $submitter,
        private SubscriptionEntitlementService $entitlements,
        private NotificationService $notifications,
    ) {
    }

    /**
     * Candidates who have opted in, for the scheduled run.
     *
     * @return \Illuminate\Support\Collection<int, AutoApplySetting>
     */
    public function subscribers()
    {
        return AutoApplySetting::with('user')->where('enabled', true)->get()
            ->filter(fn (AutoApplySetting $s) => $s->user !== null);
    }

    /**
     * Run for everyone who opted in. Returns the runs it wrote.
     *
     * One candidate failing must not stop the rest — a single malformed
     * profile should not silence the whole night's run.
     *
     * @return array<int, AutoApplyRun>
     */
    public function runAll(): array
    {
        if (! $this->settings->matching()['auto_apply_enabled']) {
            return [];
        }

        $runs = [];

        foreach ($this->subscribers() as $setting) {
            try {
                $runs[] = $this->runFor($setting->user);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $runs;
    }

    /**
     * Run for one candidate. Always returns the run it recorded.
     */
    public function runFor(User $user): AutoApplyRun
    {
        $matching = $this->settings->matching();
        $setting = $this->settingsFor($user);

        if (! $matching['auto_apply_enabled'] || ! $setting->enabled) {
            return $this->record($user, 'disabled', 0, 0);
        }

        if (! $this->matcher->readiness($user)['ready']) {
            return $this->record($user, 'not_ready', 0, 0);
        }

        $remainingToday = $setting->daily_limit - $this->appliedToday($user);

        if ($remainingToday < 1) {
            return $this->record($user, 'limit_reached', 0, 0);
        }

        // Null balance means unlimited, which is what a seeker SKU returns
        // today. It must never be read as zero — see EntitlementCatalogue.
        $balance = $this->entitlements->balance($user, 'auto_apply');

        if ($balance !== null && $balance < 1) {
            return $this->record($user, 'no_credits', 0, 0);
        }

        $allowance = $balance === null ? $remainingToday : min($remainingToday, $balance);

        $alreadyApplied = $user->applications()->pluck('job_id')->all();

        // Our own published vacancies only. Deliberately not a federated or
        // scraped feed — see the class docblock.
        $candidates = Job::with('company')
            ->where('status', 'published')
            ->whereNotIn('id', $alreadyApplied)
            ->get();

        $targets = $this->matcher
            ->rank($candidates, $user, $setting->effectiveMinimumScore($matching['minimum_match_score']))
            ->take($allowance);

        if ($targets->isEmpty()) {
            return $this->record($user, 'no_matches', $candidates->count(), 0);
        }

        $applied = 0;

        DB::transaction(function () use ($targets, $user, &$applied) {
            foreach ($targets as $job) {
                // 'Auto Apply' is not decoration. It is what lets the candidate
                // see on their applications list which ones we sent for them,
                // and withdraw any of them.
                $application = $this->submitter->submit($job, $user, 'Auto Apply');

                if ($application->wasRecentlyCreated) {
                    $applied++;
                }
            }

            if ($applied > 0) {
                $this->entitlements->consume(
                    $user,
                    'auto_apply',
                    $applied,
                    null,
                    "Auto-apply sent {$applied} applications"
                );
            }
        });

        $run = $this->record($user, $applied > 0 ? 'applied' : 'no_matches', $candidates->count(), $applied);

        if ($applied > 0) {
            $this->notify($user, $run);
        }

        return $run;
    }

    /**
     * The candidate's settings row, created off by default.
     *
     * Off is the only safe default for a feature that acts without the person
     * present.
     */
    public function settingsFor(User $user): AutoApplySetting
    {
        return AutoApplySetting::firstOrCreate(
            ['user_id' => $user->id],
            ['enabled' => false, 'daily_limit' => 5, 'minimum_score' => null]
        );
    }

    /**
     * How many auto-applications this candidate already had today.
     *
     * Counted from the applications themselves rather than from a counter
     * column, so it stays right if a run half-fails or is run twice by hand.
     */
    public function appliedToday(User $user): int
    {
        return $user->applications()
            ->where('source', 'Auto Apply')
            ->whereDate('applied_at', today())
            ->count();
    }

    /**
     * @return \Illuminate\Support\Collection<int, AutoApplyRun>
     */
    public function recentRuns(User $user, int $limit = 10)
    {
        return AutoApplyRun::where('user_id', $user->id)->latest('ran_at')->limit($limit)->get();
    }

    private function record(User $user, string $outcome, int $considered, int $applied, ?string $note = null): AutoApplyRun
    {
        $this->settingsFor($user)->update(['last_run_at' => now()]);

        return AutoApplyRun::create([
            'user_id' => $user->id,
            'ran_at' => now(),
            'considered' => $considered,
            'applied' => $applied,
            'outcome' => $outcome,
            'note' => $note,
        ]);
    }

    private function notify(User $user, AutoApplyRun $run): void
    {
        try {
            $this->notifications->send(
                $user,
                'application_status',
                'Auto-apply: '.$run->applied.' new '.($run->applied === 1 ? 'application' : 'applications'),
                $run->summary().' Open your applications to review or withdraw any of them.',
                ['auto_apply_run_id' => $run->id],
                'job_match'
            );
        } catch (\Throwable $e) {
        }
    }
}
