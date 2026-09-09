<?php

namespace Tests\Feature;

use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The signed-in header rail, and the account-type chooser it links to.
 *
 * Both existed as working back-ends that nothing in the site called:
 * /notifications/feed returned real rows for a bell that was never built, and
 * /register served an account-type chooser that no link pointed at. These
 * assertions exist so the wiring cannot quietly come undone again.
 */
class HeaderIconRailTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_header_exposes_the_notification_bell(): void
    {
        $this->seed();
        $seeker = User::where('email', 'candidate@luckyboss.test')->firstOrFail();

        // Uses a public page rather than '/': a signed-in candidate's home is
        // now Lucky AI, and '/' redirects there. What is under test here is the
        // public header's signed-in state, which this page still renders.
        $response = $this->actingAs($seeker)->get(route('seekers.public'))->assertOk();

        // The bell has to actually reach the endpoint, not just look like a bell.
        $response->assertSee(route('notifications.feed'));
        $response->assertSee(route('notifications.clear-all'));
        $response->assertSee('Mark all read');
        $response->assertSee('Sign out');
    }

    public function test_bell_reports_the_real_unread_count(): void
    {
        $this->seed();
        $seeker = User::where('email', 'candidate@luckyboss.test')->firstOrFail();

        PlatformNotification::query()->delete();
        PlatformNotification::create([
            'user_id' => $seeker->id,
            'type' => 'system_alert',
            'title' => 'Your application was shortlisted',
            'body' => 'Keppel Marine Works moved you to shortlist.',
        ]);

        $payload = $this->actingAs($seeker)
            ->getJson(route('notifications.feed'))
            ->assertOk()
            ->json();

        $this->assertSame(1, $payload['unreadCount']);
        $this->assertSame('Your application was shortlisted', $payload['notifications'][0]['title']);

        // Clearing has to actually drop the badge to zero, otherwise the header
        // shows a count that can never be dismissed.
        $this->actingAs($seeker)->post(route('notifications.clear-all'))->assertOk();

        $this->assertSame(
            0,
            $this->actingAs($seeker)->getJson(route('notifications.feed'))->json('unreadCount')
        );
    }

    public function test_register_button_leads_to_the_account_type_chooser(): void
    {
        $this->seed();

        // Guests get one Register button pointing at the chooser, not the old
        // dropdown that linked straight into the two forms.
        $home = $this->get(route('home'))->assertOk();
        $home->assertSee(route('register'));
        $home->assertDontSee('Job Seeker Register');
        $home->assertDontSee('Employer Register');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('What kind of account?')
            ->assertSee(route('register.seeker'))
            ->assertSee(route('register.employer'));
    }

    /**
     * Messaging has a model and a table but no routes, no controller and no
     * rows. Until it has a back-end, an envelope in the header would be
     * decoration that opens on nothing.
     */
    public function test_header_does_not_advertise_messaging_before_it_exists(): void
    {
        $this->seed();
        $seeker = User::where('email', 'candidate@luckyboss.test')->firstOrFail();

        // Uses a public page rather than '/': a signed-in candidate's home is
        // now Lucky AI, and '/' redirects there. What is under test here is the
        // public header's signed-in state, which this page still renders.
        $this->actingAs($seeker)
            ->get(route('seekers.public'))
            ->assertOk()
            ->assertDontSee('aria-label="Messages"', false);
    }
}
