<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin's navigation, and the button that used to do nothing.
 *
 * The rail is `hidden lg:flex`, so below 1024px it is display:none. The top bar
 * has a ☰ that toggled `mobileSidebarOpen` — and **no element in the layout read
 * that variable**. So on any window narrower than 1024px the admin had no
 * navigation at all: no rail, a dead button, and no way to reach another admin
 * screen except by typing the URL. It looked exactly like a broken page, which
 * is how sir reported it.
 *
 * These assertions are deliberately about the markup rather than about pixels: a
 * feature test cannot resize a viewport, and the defect was never a layout bug —
 * it was a binding that did not exist. Asserting the binding is present is what
 * would have caught it.
 */
class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed();

        $user = User::factory()->create(['email' => 'navadmin@luckyboss.test', 'password' => 'password123']);
        $user->roles()->attach(Role::where('slug', 'super-admin')->value('id'));

        return $user;
    }

    public function test_the_drawer_toggle_is_actually_wired_to_the_sidebar(): void
    {
        $page = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();

        // The button that sets it...
        $page->assertSee('mobileSidebarOpen = !mobileSidebarOpen', false);

        // ...and the element that reads it. Without this second assertion the
        // first one passes on a button wired to nothing, which is the whole bug.
        $page->assertSee("mobileSidebarOpen ? 'flex' : 'hidden lg:flex'", false);
    }

    public function test_the_drawer_can_be_dismissed(): void
    {
        $page = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();

        // A backdrop that closes it, and Escape. An overlay covering the page
        // with no way out but the ☰ is its own trap on a narrow screen.
        $page->assertSee('@click="mobileSidebarOpen = false"', false);
        $page->assertSee('keydown.escape.window', false);
    }

    public function test_widening_the_window_resets_the_drawer(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('closeDrawerOnDesktop()', false);
    }

    public function test_the_rail_still_carries_every_admin_section(): void
    {
        $page = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();

        foreach ([
            'Dashboard', 'Employers', 'Candidates', 'Job Listings', 'ATS Pipeline',
            'Subscriptions', 'AI & APIs', 'Masters', 'CMS', 'Settings',
        ] as $section) {
            $page->assertSee($section, false);
        }
    }

    public function test_the_two_layouts_declare_themselves_so_soft_nav_cannot_mix_them(): void
    {
        $admin = $this->admin();

        /*
            The bug this pins was not a layout bug at all.

            The site's soft navigation swaps only <main>. Signing in at /login
            posts through that handler, which followed the redirect to /admin,
            found a perfectly good <main> in it, and swapped that <main> into the
            *sign-in page's* body — discarding the admin's rail, its top bar and
            its Alpine root. The admin then had no navigation whatsoever, and a
            refresh fixed it, which is what made it look intermittent.

            The guard compares the layout each document declares, so both have to
            carry the attribute or the comparison silently passes.
        */
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-layout="admin"', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-layout="app"', false);
    }

    public function test_the_soft_navigation_refuses_to_swap_across_layouts(): void
    {
        $this->seed();

        // The guard itself, and the fallback landing on where the response
        // actually resolved rather than on the URL that was requested — without
        // that second part a successful admin sign-in went back to /login as a
        // GET, logged in but staring at the sign-in form.
        $this->get(route('login'))
            ->assertOk()
            ->assertSee("throw new Error('layout change')", false)
            ->assertSee('hardNav(landing)', false);
    }

    public function test_a_candidate_cannot_open_the_admin(): void
    {
        $this->seed();

        $user = User::factory()->create(['email' => 'notadmin@luckyboss.test', 'password' => 'password123']);
        $user->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        // Turned away, not shown an empty admin. It is a redirect rather than a
        // 403 — the guard sends non-admins back rather than announcing that the
        // page exists.
        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect();
    }
}
