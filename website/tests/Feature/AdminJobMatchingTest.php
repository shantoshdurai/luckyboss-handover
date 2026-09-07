<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin screen behind SiteSettingsService::matching().
 *
 * Those four values have been read on every seeker dashboard since Apply All
 * shipped, but nothing ever wrote them, so the platform always ran on defaults
 * and the threshold sir keeps asking to demo could not be changed at all. What
 * matters here is the round trip — that a saved number is what the seeker side
 * subsequently reads — and that the clamps still hold, because a threshold of
 * 900 empties every candidate's job list with no visible cause.
 */
class AdminJobMatchingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@luckyboss.test')->firstOrFail();
    }

    public function test_the_screen_renders_the_current_values(): void
    {
        $this->seed();

        $this->actingAs($this->admin())
            ->get(route('admin.job-matching.edit'))
            ->assertOk()
            ->assertSee('Match Threshold')
            ->assertSee('value="60"', false);
    }

    public function test_a_saved_threshold_is_what_the_seeker_side_reads(): void
    {
        $this->seed();

        $this->actingAs($this->admin())
            ->put(route('admin.job-matching.update'), [
                'minimum_match_score' => 80,
                'bulk_apply_enabled' => '1',
                'bulk_apply_limit' => 10,
                'auto_apply_enabled' => '0',
            ])
            ->assertRedirect();

        $matching = app(SiteSettingsService::class)->matching();

        $this->assertSame(80, $matching['minimum_match_score']);
        $this->assertSame(10, $matching['bulk_apply_limit']);
        $this->assertTrue($matching['bulk_apply_enabled']);
        $this->assertFalse($matching['auto_apply_enabled']);
    }

    public function test_unchecked_switches_persist_as_off(): void
    {
        // The failure this pins down: an unchecked checkbox posts nothing at
        // all, so without the paired hidden input a disabled Apply All would
        // silently come back on with the stored default on the next save.
        $this->seed();

        $this->actingAs($this->admin())
            ->put(route('admin.job-matching.update'), [
                'minimum_match_score' => 70,
                'bulk_apply_limit' => 25,
                // bulk_apply_enabled and auto_apply_enabled deliberately absent.
            ])
            ->assertRedirect();

        $this->assertFalse(app(SiteSettingsService::class)->matching()['bulk_apply_enabled']);
    }

    public function test_an_out_of_range_threshold_is_rejected(): void
    {
        $this->seed();

        $this->actingAs($this->admin())
            ->put(route('admin.job-matching.update'), [
                'minimum_match_score' => 900,
                'bulk_apply_limit' => 25,
            ])
            ->assertSessionHasErrors('minimum_match_score');

        $this->assertSame(60, app(SiteSettingsService::class)->matching()['minimum_match_score']);
    }

    public function test_a_bulk_apply_cap_above_one_hundred_is_rejected(): void
    {
        $this->seed();

        $this->actingAs($this->admin())
            ->put(route('admin.job-matching.update'), [
                'minimum_match_score' => 70,
                'bulk_apply_limit' => 5000,
            ])
            ->assertSessionHasErrors('bulk_apply_limit');
    }

    public function test_a_candidate_cannot_reach_the_screen(): void
    {
        $this->seed();
        $candidate = User::where('email', 'candidate@luckyboss.test')->firstOrFail();

        $this->actingAs($candidate)->get(route('admin.job-matching.edit'))->assertForbidden();
        $this->actingAs($candidate)->put(route('admin.job-matching.update'), [
            'minimum_match_score' => 0,
            'bulk_apply_limit' => 100,
        ])->assertForbidden();
    }
}
