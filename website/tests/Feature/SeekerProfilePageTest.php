<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The view-first profile.
 *
 * Every field used to live in one 396-line always-editing form, so a candidate
 * could never simply look at what employers see, and had no sense of what was
 * still blank. This is the missing half; the editor still exists behind it.
 */
class SeekerProfilePageTest extends TestCase
{
    use RefreshDatabase;

    private function candidate(): User
    {
        return User::where('email', 'candidate@luckyboss.test')->firstOrFail();
    }

    public function test_the_profile_reads_before_it_edits(): void
    {
        $this->seed();

        $this->actingAs($this->candidate())
            ->get(route('seeker.profile.show'))
            ->assertOk()
            ->assertSee('Profile complete')
            ->assertSee('Skills')
            ->assertSee('Documents');
    }

    public function test_the_long_form_is_still_reachable_behind_it(): void
    {
        $this->seed();

        $this->actingAs($this->candidate())
            ->get(route('seeker.profile.edit'))
            ->assertOk();
    }

    public function test_no_empty_social_proof_tabs_are_shipped(): void
    {
        $this->seed();

        // TickBig carry Achievements, Testimonials and Ratings. We do not, until
        // there is something real to put in them — an empty social-proof tab is
        // the fabricated-content failure this project keeps repeating.
        $this->actingAs($this->candidate())
            ->get(route('seeker.profile.show'))
            ->assertOk()
            ->assertDontSee('Testimonials')
            ->assertDontSee('Ratings');
    }

    public function test_an_employer_cannot_open_a_candidate_profile_page(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'employer@luckyboss.test')->firstOrFail())
            ->get(route('seeker.profile.show'))
            ->assertForbidden();
    }
}
