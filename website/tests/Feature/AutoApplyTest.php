<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\AutoApplyRun;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Role;
use App\Models\User;
use App\Services\AutoApplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auto-apply — applying on a candidate's behalf while they are not watching.
 *
 * This is the most dangerous feature in the product: it acts in a real
 * person's name, in front of real employers, with nobody looking. Every test
 * here exists because of a way it could do that wrongly.
 *
 * The four gates, each with a test:
 *   1. The platform switch is off  -> nothing is sent.
 *   2. The candidate did not opt in -> nothing is sent, however the platform is set.
 *   3. The profile cannot be scored -> nothing is sent (never a guessed score).
 *   4. The daily limit is reached   -> nothing is sent.
 *
 * Plus the two that make it honest afterwards: every run is logged with its
 * reason even when it sends nothing, and everything sent is tagged 'Auto Apply'
 * so the candidate can find and withdraw it.
 */
class AutoApplyTest extends TestCase
{
    use RefreshDatabase;

    private function candidate(bool $employable = true): User
    {
        Role::firstOrCreate(['slug' => 'job-seeker'], ['name' => 'Job Seeker', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Ravi Kumar',
            'email' => 'ravi'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        CandidateProfile::create([
            'user_id' => $user->id,
            'country_code' => 'SG',
            // A profile the matcher will refuse to score is the default for
            // the not-ready test; everything else gets enough to compare.
            'current_title' => $employable ? 'Electrician' : null,
            'current_location' => $employable ? 'Singapore' : null,
            'years_experience' => $employable ? 5 : null,
        ]);

        if ($employable) {
            $user->candidateProfile->update(['skills' => ['Electrical Wiring', 'Maintenance']]);
        }

        return $user->fresh();
    }

    private function vacancy(string $title = 'Site Electrician'): Job
    {
        $company = Company::create(['name' => 'Acme Builders', 'country_code' => 'SG', 'status' => 'active']);

        return Job::create([
            'company_id' => $company->id,
            'title' => $title,
            'description' => 'Electrical wiring and maintenance on site. Five years experience.',
            'country_code' => 'SG',
            'location' => 'Singapore',
            'experience_min' => 3,
            'experience_max' => 8,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    private function platformSwitch(bool $on): void
    {
        AdminRecord::updateOrCreate(
            ['module' => 'matching', 'slug' => 'job-matching'],
            ['name' => 'Job Matching', 'payload' => ['auto_apply_enabled' => $on, 'minimum_match_score' => 40]]
        );
    }

    private function optIn(User $user, array $overrides = []): void
    {
        app(AutoApplyService::class)->settingsFor($user)->update(array_merge(['enabled' => true], $overrides));
    }

    // ── The four gates ────────────────────────────────────────────────────

    public function test_nothing_is_sent_while_the_platform_switch_is_off(): void
    {
        $this->platformSwitch(false);
        $user = $this->candidate();
        $this->optIn($user);
        $this->vacancy();

        $run = app(AutoApplyService::class)->runFor($user);

        $this->assertSame('disabled', $run->outcome);
        $this->assertSame(0, JobApplication::count());
    }

    public function test_nothing_is_sent_for_a_candidate_who_did_not_opt_in(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate();
        $this->vacancy();

        // Deliberately no optIn(). An administrator turning the platform switch
        // on is not consent from this person.
        $run = app(AutoApplyService::class)->runFor($user);

        $this->assertSame('disabled', $run->outcome);
        $this->assertSame(0, JobApplication::count());
    }

    public function test_a_profile_that_cannot_be_scored_is_never_applied_for(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate(employable: false);
        $this->optIn($user);
        $this->vacancy();

        $run = app(AutoApplyService::class)->runFor($user);

        $this->assertSame('not_ready', $run->outcome);
        $this->assertSame(0, JobApplication::count());
    }

    public function test_the_daily_limit_stops_the_run(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate();
        $this->optIn($user, ['daily_limit' => 1]);

        $this->vacancy('Site Electrician');
        $this->vacancy('Maintenance Electrician');

        $service = app(AutoApplyService::class);

        $first = $service->runFor($user);
        $this->assertSame('applied', $first->outcome);
        $this->assertSame(1, $first->applied);

        $second = $service->runFor($user);
        $this->assertSame('limit_reached', $second->outcome);
        $this->assertSame(1, JobApplication::count(), 'The second run must not exceed the daily limit.');
    }

    // ── What it does when it does run ─────────────────────────────────────

    public function test_it_applies_and_tags_the_application_so_it_can_be_found_and_withdrawn(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate();
        $this->optIn($user);
        $job = $this->vacancy();

        $run = app(AutoApplyService::class)->runFor($user);

        $this->assertSame('applied', $run->outcome);
        $this->assertSame(1, $run->applied);

        $application = JobApplication::where('candidate_id', $user->id)->firstOrFail();
        $this->assertSame($job->id, $application->job_id);
        $this->assertSame('Auto Apply', $application->source);
    }

    public function test_it_never_applies_twice_to_the_same_vacancy(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate();
        $this->optIn($user);
        $this->vacancy();

        $service = app(AutoApplyService::class);
        $service->runFor($user);

        // A second day's worth of headroom, same single vacancy.
        JobApplication::where('candidate_id', $user->id)->update(['applied_at' => now()->subDays(2)]);

        $second = $service->runFor($user);

        $this->assertSame('no_matches', $second->outcome);
        $this->assertSame(1, JobApplication::count());
    }

    public function test_the_candidates_own_floor_wins_over_a_lower_platform_minimum(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate();
        // 95 is the ceiling the screen allows, and our scorer caps a match by how
        // much of the profile it could compare — so this must match nothing.
        $this->optIn($user, ['minimum_score' => 95]);
        $this->vacancy();

        $run = app(AutoApplyService::class)->runFor($user);

        $this->assertSame('no_matches', $run->outcome);
        $this->assertSame(0, JobApplication::count());
    }

    // ── Honesty afterwards ────────────────────────────────────────────────

    public function test_every_run_is_logged_with_a_reason_even_when_it_sends_nothing(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate(employable: false);
        $this->optIn($user);

        app(AutoApplyService::class)->runFor($user);

        $run = AutoApplyRun::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('not_ready', $run->outcome);
        $this->assertStringContainsString('Nothing was sent', $run->summary());
    }

    // ── The screen ────────────────────────────────────────────────────────

    public function test_the_screen_says_it_is_not_running_while_the_platform_switch_is_off(): void
    {
        $this->platformSwitch(false);
        $user = $this->candidate();
        $this->optIn($user);

        $this->actingAs($user)
            ->get(route('seeker.auto-apply.edit'))
            ->assertOk()
            ->assertSee('Auto-apply is not running')
            ->assertSee('switched off across the platform');
    }

    public function test_the_screen_refuses_to_switch_on_for_an_unscoreable_profile(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate(employable: false);

        $this->actingAs($user)
            ->put(route('seeker.auto-apply.update'), ['enabled' => '1', 'daily_limit' => 5])
            ->assertRedirect();

        $this->assertFalse(app(AutoApplyService::class)->settingsFor($user)->enabled);
    }

    public function test_a_candidate_cannot_set_a_floor_below_the_platform_minimum(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate();

        $this->actingAs($user)
            ->put(route('seeker.auto-apply.update'), ['enabled' => '1', 'minimum_score' => 10, 'daily_limit' => 5])
            ->assertSessionHasErrors('minimum_score');
    }

    public function test_settings_round_trip(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate();

        $this->actingAs($user)
            ->put(route('seeker.auto-apply.update'), ['enabled' => '1', 'minimum_score' => 75, 'daily_limit' => 8])
            ->assertRedirect();

        $setting = app(AutoApplyService::class)->settingsFor($user);
        $this->assertTrue($setting->enabled);
        $this->assertSame(75, $setting->minimum_score);
        $this->assertSame(8, $setting->daily_limit);
    }

    public function test_the_console_command_does_nothing_while_the_platform_switch_is_off(): void
    {
        $this->platformSwitch(false);
        $user = $this->candidate();
        $this->optIn($user);
        $this->vacancy();

        $this->artisan('auto-apply:run')
            ->expectsOutputToContain('switched off')
            ->assertSuccessful();

        $this->assertSame(0, JobApplication::count());
    }

    public function test_the_dry_run_sends_nothing(): void
    {
        $this->platformSwitch(true);
        $user = $this->candidate();
        $this->optIn($user);
        $this->vacancy();

        $this->artisan('auto-apply:run --dry')
            ->expectsOutputToContain('Nothing was sent')
            ->assertSuccessful();

        $this->assertSame(0, JobApplication::count());
    }
}
