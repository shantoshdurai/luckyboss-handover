<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AutoApplyService;
use App\Services\SiteSettingsService;
use Illuminate\Console\Command;

class RunAutoApply extends Command
{
    protected $signature = 'auto-apply:run
                            {--user= : Run for one candidate by id or email, ignoring the schedule}
                            {--dry : Report what would be sent without sending anything}';

    protected $description = 'Apply to matching Lucky Boss vacancies for candidates who opted in';

    public function handle(AutoApplyService $service, SiteSettingsService $settings): int
    {
        if (! $settings->matching()['auto_apply_enabled']) {
            $this->warn('Auto-apply is switched off in Site Settings → Job Matching. Nothing ran.');

            return self::SUCCESS;
        }

        if ($this->option('dry')) {
            return $this->dryRun($service);
        }

        $runs = $this->option('user')
            ? [$service->runFor($this->resolveUser())]
            : $service->runAll();

        if ($runs === []) {
            $this->info('No candidates have auto-apply switched on.');

            return self::SUCCESS;
        }

        $applied = 0;

        foreach ($runs as $run) {
            $applied += $run->applied;
            $this->line(sprintf(
                '  %-30s %-14s %d applied of %d considered',
                $run->user?->email ?? "user {$run->user_id}",
                $run->outcome,
                $run->applied,
                $run->considered
            ));
        }

        $this->info(sprintf('%d run(s), %d application(s) sent.', count($runs), $applied));

        return self::SUCCESS;
    }

    /**
     * Report without applying.
     *
     * The point of --dry is the instruction in CLAUDE.md that auto-apply stays
     * off "until someone has watched it run". This is how you watch it.
     */
    private function dryRun(AutoApplyService $service): int
    {
        $subscribers = $this->option('user')
            ? collect([$service->settingsFor($this->resolveUser())])
            : $service->subscribers();

        if ($subscribers->isEmpty()) {
            $this->info('No candidates have auto-apply switched on.');

            return self::SUCCESS;
        }

        $matching = app(SiteSettingsService::class)->matching();
        $matcher = app(\App\Services\JobMatchService::class);

        foreach ($subscribers as $setting) {
            $user = $setting->user;
            $floor = $setting->effectiveMinimumScore($matching['minimum_match_score']);

            if (! $matcher->readiness($user)['ready']) {
                $this->line("  {$user->email}: profile too thin to score — would send nothing.");

                continue;
            }

            $applied = $user->applications()->pluck('job_id')->all();
            $room = max(0, $setting->daily_limit - $service->appliedToday($user));

            $targets = $matcher->rank(
                \App\Models\Job::with('company')->where('status', 'published')->whereNotIn('id', $applied)->get(),
                $user,
                $floor
            )->take($room);

            $this->line("  {$user->email}: would apply to {$targets->count()} job(s) at ≥{$floor}% (room for {$room} today)");

            foreach ($targets as $job) {
                $score = $matcher->score($job, $user)['score'] ?? null;
                $this->line('      · '.$job->title.' — '.($score === null ? 'not scored' : $score.'%'));
            }
        }

        $this->comment('Dry run. Nothing was sent.');

        return self::SUCCESS;
    }

    private function resolveUser(): User
    {
        $needle = (string) $this->option('user');

        $user = User::where('id', $needle)->orWhere('email', $needle)->first();

        if (! $user) {
            throw new \RuntimeException("No user matching '{$needle}'.");
        }

        return $user;
    }
}
