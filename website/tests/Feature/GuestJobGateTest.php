<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Where the sign-in gate sits for a signed-out visitor.
 *
 * The rule sir asked for: browsing the vacancies is open, opening one is not.
 * The nav used to invert that — "Opportunities" sent a guest straight to the
 * sign-in form before they had seen a single job, which is asking for the
 * account before showing the reason to want one.
 */
class GuestJobGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunities_nav_scrolls_instead_of_gating(): void
    {
        $this->seed();

        $home = $this->get(route('home'))->assertOk();

        // The anchor exists and the nav points at it.
        $home->assertSee('id="opportunities"', false);
        $home->assertSee('#opportunities', false);
    }

    public function test_a_guest_can_see_the_vacancies_but_not_open_one(): void
    {
        $this->seed();

        $home = $this->get(route('home'))->assertOk();

        // The section itself is visible to a signed-out visitor.
        $home->assertSee('Featured');

        // Every job card routes to sign-in rather than through to the listing.
        // The keyword query only ever comes from a job card; category links use
        // ?category=, so its absence is a precise signal that the gate holds.
        $home->assertDontSee('keyword=');
        $home->assertSee(route('login'));
    }

    public function test_a_signed_in_visitor_follows_jobs_straight_through(): void
    {
        $this->seed();

        // Signed in as an employer, not a candidate: the gate on the home page
        // is auth()->check(), so any signed-in visitor exercises it — and a
        // candidate can no longer reach '/' at all, because their home screen
        // is now Lucky AI and '/' redirects there.
        $user = User::where('email', 'employer@luckyboss.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('keyword=');
    }

    public function test_sign_in_page_names_itself_and_greets_inside_the_card(): void
    {
        $this->seed();

        $login = $this->get(route('login'))->assertOk();

        // The page title says what the page is; the greeting is card-level.
        $login->assertSee('Sign in');
        $login->assertSee('Welcome back');
        $login->assertSeeInOrder(['Sign in', 'Welcome back']);
    }

    /**
     * Both doors are offered under the sign-in card, and which one shows
     * follows the "I am a" toggle. Only the employer line used to be there, so
     * a job seeker who could not sign in was invited to register a company.
     */
    public function test_sign_in_offers_both_account_types_bound_to_the_toggle(): void
    {
        $this->seed();

        $login = $this->get(route('login'))->assertOk();

        $login->assertSee('Create a job seeker account');
        $login->assertSee('Register your company');
        $login->assertSee(route('register.seeker'));
        $login->assertSee(route('register.employer'));

        // Each offer is bound to the toggle rather than always-on.
        $login->assertSee("x-show=\"role === 'job-seeker'\"", false);
        $login->assertSee("x-show=\"role === 'employer'\"", false);

        // No x-cloak on them: with scripting off both must still be reachable
        // rather than hidden forever.
        $this->assertStringNotContainsString(
            'x-cloak" x-show="role',
            $login->getContent()
        );
    }

    /**
     * The homepage advertised "Explore All 5,000+ Jobs" against a database
     * holding a fraction of that. The same invented figures were stripped from
     * the sign-in page already; this stops them coming back anywhere else.
     */
    public function test_no_invented_totals_are_advertised(): void
    {
        $this->seed();

        foreach ([route('home'), route('seekers.public')] as $url) {
            $response = $this->get($url)->assertOk();
            $response->assertDontSee('5,000+');
            $response->assertDontSee('2,500+');
            $response->assertDontSee('50,000+');
        }
    }
}
