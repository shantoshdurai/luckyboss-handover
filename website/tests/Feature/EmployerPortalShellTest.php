<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Country;
use App\Models\Job;
use App\Models\Package;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The employer portal after it stopped being two websites.
 *
 * Five of its six screens used to render `x-employer-sidebar`, a second complete
 * <html> document with its own logo bar and a twelve-item left rail. The header's
 * pill nav linked into it, so four of the five pills dropped the top bar, the
 * logo, and — the part that made it feel broken rather than merely inconsistent —
 * the soft navigation, because <main> cannot be swapped between two documents.
 *
 * These tests pin the shell, the nav and the plan panel, because all three are
 * the kind of thing that looks fine in a screenshot of the one page you checked.
 */
class EmployerPortalShellTest extends TestCase
{
    use RefreshDatabase;

    private function employer(): User
    {
        $user = User::factory()->create(['email' => 'shell@acme.test', 'password' => 'password123']);
        $user->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $company = Company::create([
            'name' => 'Shell Test Co',
            'country_code' => 'SG',
            'status' => 'verified',
        ]);
        $company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);

        return $user;
    }

    public function test_every_employer_screen_renders_the_site_header(): void
    {
        $this->seed();
        $user = $this->employer();

        // The logo lives in the site header and nowhere else now, so its
        // presence is the cheapest honest proof the shell is the shared one.
        foreach ([
            '/employer',
            '/employer/dashboard',
            '/employer/jobs',
            '/employer/jobs/create',
            '/employer/portal/candidates',
            '/employer/subscription',
        ] as $url) {
            $page = $this->actingAs($user)->get($url)->assertOk();

            $page->assertSee('Luckyboss Employment Agency Pte. Ltd', false);
            $page->assertSee('Hiring AI', false);
            $page->assertSee('Posted Jobs', false);
        }
    }

    public function test_no_employer_screen_renders_a_second_document(): void
    {
        $this->seed();
        $user = $this->employer();

        foreach (['/employer/dashboard', '/employer/jobs', '/employer/portal/candidates', '/employer/subscription'] as $url) {
            $html = $this->actingAs($user)->get($url)->getContent();

            // Two documents would mean the old rail is back. Counted on the
            // closing tag: the opening one also appears inside a JS comment in
            // the layout, which is exactly the kind of thing that makes a
            // grep-based assertion pass or fail for the wrong reason.
            $this->assertSame(1, substr_count($html, '</html>'), "{$url} rendered more than one document");
        }
    }

    public function test_the_nav_offers_subscription_and_not_offers(): void
    {
        $this->seed();
        $user = $this->employer();

        $page = $this->actingAs($user)->get('/employer/dashboard')->assertOk();

        $page->assertSee(route('employer.subscription'), false);
        // Offers is still reachable, just from the account menu rather than a pill.
        $page->assertSee(route('employer.portal', 'offers'), false);
        $page->assertSee(route('employer.portal', 'company-profile'), false);
        $page->assertSee(route('employer.portal', 'team'), false);
    }

    public function test_a_company_with_no_vacancies_gets_the_empty_state(): void
    {
        $this->seed();
        $user = $this->employer();

        $page = $this->actingAs($user)->get('/employer/jobs')->assertOk();

        $page->assertSee('No jobs posted yet');
        $page->assertSee('Post your first job');
        // The table chrome has no business being drawn around nothing.
        $page->assertDontSee('Position &amp; Category', false);
    }

    public function test_once_a_vacancy_exists_the_list_replaces_the_empty_state(): void
    {
        $this->seed();
        $user = $this->employer();
        $company = $user->companies()->first();

        Job::create([
            'company_id' => $company->id,
            'title' => 'Site Supervisor',
            'description' => 'Runs the site.',
            'country_code' => 'SG',
            'location' => 'Jurong East',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $page = $this->actingAs($user)->get('/employer/jobs')->assertOk();

        $page->assertDontSee('No jobs posted yet');
        $page->assertSee('Site Supervisor');
        // Edit and archive are what makes it a list they own rather than a report.
        $page->assertSee('Edit');
        $page->assertSee('Archive');
    }

    public function test_the_dashboard_plan_panel_names_the_real_plan(): void
    {
        $this->seed();
        $user = $this->employer();

        $page = $this->actingAs($user)->get('/employer/dashboard')->assertOk();

        // Hardcoded for every employer on the platform until 2026-09-10, on a
        // tier that does not exist in `packages`.
        $page->assertDontSee('Enterprise Pro');
        $page->assertDontSee('Unlimited Active Posts');
        $page->assertDontSee('NLP Engine v2');

        // This company has no subscription, and the panel must say so.
        $page->assertSee('No plan');
    }

    public function test_a_company_on_a_plan_sees_that_plan_named(): void
    {
        $this->seed();
        $user = $this->employer();
        $company = $user->companies()->first();
        $package = Package::where('slug', 'professional')->firstOrFail();

        $company->subscriptions()->create([
            'package_id' => $package->id,
            'status' => 'active',
            'starts_at' => today(),
            'expires_at' => today()->addDays(30),
            'entitlements' => $package->entitlements,
            'currency_code' => 'SGD',
            'amount' => 0,
        ]);

        $this->actingAs($user)->get('/employer/dashboard')
            ->assertOk()
            ->assertSee('Professional')
            ->assertDontSee('No plan');
    }

    public function test_choosing_a_plan_at_sign_up_starts_it_unpaid(): void
    {
        $this->seed();
        $country = Country::where('code', 'IN')->firstOrFail();
        $package = Package::where('slug', 'professional')->firstOrFail();

        $this->post(route('register.employer.store'), [
            'name' => 'Meera Raj',
            'email' => 'meera@plan.test',
            'dial_code' => '+91',
            'phone_national' => '9876500011',
            'company_name' => 'Plan Test Industries',
            'country_code' => $country->code,
            'package_id' => $package->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => 'on',
        ])->assertRedirect(route('employer.home'));

        $company = Company::where('name', 'Plan Test Industries')->firstOrFail();
        $subscription = $company->subscriptions()->firstOrFail();

        $this->assertSame($package->id, $subscription->package_id);
        $this->assertSame('active', $subscription->status);
        // Priced in the currency of the country they registered in, and charged
        // nothing — there is no gateway, so any other amount would be a payment
        // in the ledger that never happened.
        $this->assertSame('INR', $subscription->currency_code);
        $this->assertEquals(0, $subscription->amount);
    }

    public function test_registering_without_choosing_a_plan_still_creates_the_account(): void
    {
        $this->seed();
        $country = Country::where('is_active', true)->firstOrFail();

        $this->post(route('register.employer.store'), [
            'name' => 'No Plan',
            'email' => 'noplan@plan.test',
            'dial_code' => '+65',
            'phone_national' => '80005555',
            'company_name' => 'No Plan Co',
            'country_code' => $country->code,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => 'on',
        ])->assertRedirect(route('employer.home'));

        $company = Company::where('name', 'No Plan Co')->firstOrFail();
        $this->assertSame(0, $company->subscriptions()->count());
    }

    public function test_the_plan_step_shows_three_priced_tiers_with_the_middle_one_recommended(): void
    {
        $this->seed();

        $page = $this->get(route('register.employer'))->assertOk();

        $page->assertSee('Choose your plan');
        $page->assertSee('Step <span data-wizard-current>1</span> of 4', false);

        foreach (['Starter', 'Professional', 'Enterprise'] as $tier) {
            $page->assertSee($tier);
        }

        // One card starts chosen, and it is the middle one.
        $professional = Package::where('slug', 'professional')->firstOrFail();
        $page->assertSee('value="'.$professional->id.'"', false);
        // Blade's @checked emits the bare attribute, not checked="checked".
        $this->assertSame(1, substr_count($page->getContent(), ' checked>'));
        $page->assertSee('Recommended');

        // Nothing is charged, and the page has to say so rather than let anyone
        // assume a card is about to be taken.
        $page->assertSee('Nothing is charged today');
    }
}
