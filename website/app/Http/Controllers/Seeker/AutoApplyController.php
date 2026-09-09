<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Services\AutoApplyService;
use App\Services\JobMatchService;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The candidate's own control over auto-apply.
 *
 * Deliberately a separate screen from the profile: switching on something that
 * acts for you while you are asleep deserves its own page with its own
 * explanation, not a checkbox buried in a settings form.
 */
class AutoApplyController extends Controller
{
    public function __construct(private AutoApplyService $autoApply)
    {
    }

    public function edit(): View
    {
        $user = $this->seeker();

        return view('seeker.auto-apply.edit', [
            'setting' => $this->autoApply->settingsFor($user),
            'matching' => app(SiteSettingsService::class)->matching(),
            'readiness' => app(JobMatchService::class)->readiness($user),
            'runs' => $this->autoApply->recentRuns($user),
            'appliedToday' => $this->autoApply->appliedToday($user),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->seeker();
        $platformMinimum = app(SiteSettingsService::class)->matching()['minimum_match_score'];

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            // Their floor cannot sit below the platform's, because the platform
            // minimum is the point below which we do not trust our own score.
            'minimum_score' => ['nullable', 'integer', 'min:'.$platformMinimum, 'max:95'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:25'],
        ], [
            'minimum_score.min' => "The lowest match score we will apply at is {$platformMinimum}%.",
            'daily_limit.max' => 'Twenty-five a day is the ceiling. Beyond that it reads as spam to employers.',
        ]);

        $enabling = $request->boolean('enabled');

        // Refusing to switch on for a profile we cannot score is the same rule
        // the matcher follows. Turning it on regardless would produce a screen
        // that says "on" above a run log that says "not ready" every night.
        if ($enabling && ! app(JobMatchService::class)->readiness($user)['ready']) {
            return back()->with('info', 'Add your skills or upload your resume first — we will not apply on your behalf until we can score you honestly.');
        }

        $this->autoApply->settingsFor($user)->update([
            'enabled' => $enabling,
            'minimum_score' => $data['minimum_score'] ?? null,
            'daily_limit' => $data['daily_limit'],
        ]);

        return back()->with('success', $enabling
            ? 'Auto-apply is on. We will look for matches every night and tell you what we sent.'
            : 'Auto-apply is off. Nothing will be sent on your behalf.');
    }

    /**
     * Run it now, on demand.
     *
     * The overnight schedule is the product; this is how a candidate (and sir,
     * in a demo) sees it work without waiting until 2am. Throttled in the route.
     */
    public function runNow(): RedirectResponse
    {
        $user = $this->seeker();
        $matching = app(SiteSettingsService::class)->matching();

        if (! $matching['auto_apply_enabled']) {
            return back()->with('info', 'Auto-apply is switched off across Lucky Boss right now.');
        }

        if (! $this->autoApply->settingsFor($user)->enabled) {
            return back()->with('info', 'Switch auto-apply on first.');
        }

        $run = $this->autoApply->runFor($user);

        return back()->with($run->applied > 0 ? 'success' : 'info', $run->summary());
    }

    private function seeker(): \App\Models\User
    {
        $user = auth()->user();
        abort_unless($user && $user->hasRole('job-seeker'), 403);

        return $user;
    }
}
