<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\User;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The screens around the ledger, and the one place enforcement actually bites.
 *
 * The employer-facing page is TickBig's shape — a plain Name / Remaining table —
 * so the test that matters is that it answers "how many do I have left" with a
 * real number, and that while charging is off it says so rather than implying a
 * limit it does not apply.
 */
class EntitlementScreensTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@luckyboss.test')->firstOrFail();
    }

    private function employer(): User
    {
        return User::where('email', 'employer@luckyboss.test')->firstOrFail();
    }

    private function enforce(bool $on): void
    {
        AdminRecord::updateOrCreate(
            ['module' => 'billing', 'slug' => 'entitlements'],
            ['name' => 'Entitlement enforcement', 'payload' => ['enforcement_enabled' => $on], 'is_active' => true]
        );
    }

    public function test_an_employer_sees_what_they_have_left(): void
    {
        $this->seed();

        $this->actingAs($this->employer())
            ->get(route('employer.subscription'))
            ->assertOk()
            ->assertSee('What you have left')
            ->assertSee('Post a vacancy')
            // Charging is off by default, and the page must say so plainly.
            ->assertSee('Everything is free while we get started.');
    }

    public function test_no_price_is_invented_before_sir_signs_them_off(): void
    {
        $this->seed();

        // A placeholder number on a billing page is the kind of thing that gets
        // quoted back to us.
        $this->actingAs($this->employer())
            ->get(route('employer.subscription'))
            ->assertOk()
            ->assertSee('Price not set yet');
    }

    public function test_a_candidate_cannot_open_the_employer_subscription_page(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'candidate@luckyboss.test')->firstOrFail())
            ->get(route('employer.subscription'))
            ->assertForbidden();
    }

    public function test_an_admin_can_grant_credits(): void
    {
        $this->seed();
        $company = Company::firstOrFail();

        $this->actingAs($this->admin())
            ->post(route('admin.entitlements.grant'), [
                'company_id' => $company->id,
                'key' => 'job_post',
                'quantity' => 25,
                'note' => 'Invoice 1042',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('entitlement_ledger', [
            'owner_id' => $company->id,
            'key' => 'job_post',
            'delta' => 25,
            'source' => 'admin_grant',
        ]);
    }

    public function test_a_credit_that_is_not_sold_is_refused(): void
    {
        $this->seed();

        // Without this a typo creates a balance nothing can ever spend.
        $this->actingAs($this->admin())
            ->post(route('admin.entitlements.grant'), [
                'company_id' => Company::firstOrFail()->id,
                'key' => 'apply',
                'quantity' => 5,
            ])
            ->assertSessionHasErrors('key');
    }

    public function test_a_candidate_cannot_grant_themselves_credits(): void
    {
        $this->seed();

        $this->actingAs(User::where('email', 'candidate@luckyboss.test')->firstOrFail())
            ->post(route('admin.entitlements.grant'), [
                'company_id' => Company::firstOrFail()->id,
                'key' => 'job_post',
                'quantity' => 999,
            ])
            ->assertForbidden();
    }

    public function test_posting_a_vacancy_is_recorded_but_never_blocked_while_charging_is_off(): void
    {
        $this->seed();
        $this->enforce(false);

        $employer = $this->employer();
        $company = $employer->companies()->firstOrFail();
        $before = Job::where('company_id', $company->id)->count();

        // Far more than the single free-tier credit.
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($employer)->post(route('employer.jobs.store'), $this->jobPayload($i))->assertRedirect();
        }

        $this->assertSame($before + 3, Job::where('company_id', $company->id)->count(), 'nothing may be blocked while charging is off');
        $this->assertSame(3, \App\Models\EntitlementLedger::where('owner_id', $company->id)
            ->where('key', 'job_post')->where('source', 'consumption')->count());
    }

    public function test_with_charging_on_an_employer_out_of_credits_is_refused(): void
    {
        $this->seed();
        $this->enforce(true);

        $employer = $this->employer();
        $company = $employer->companies()->firstOrFail();

        // Spend whatever they hold — the seeded demo company carries an active
        // Professional plan, so this is the free tier *plus* the plan's monthly
        // allowance, not a single credit.
        $entitlements = app(SubscriptionEntitlementService::class);
        $entitlements->consume($company, 'job_post', $entitlements->balance($company, 'job_post'));

        $this->assertSame(0, $entitlements->balance($company, 'job_post'));
        $before = Job::where('company_id', $company->id)->count();

        $this->actingAs($employer)
            ->post(route('employer.jobs.store'), $this->jobPayload(99))
            ->assertRedirect();

        // The vacancy must not exist: the check runs before the job is created,
        // so a refusal never leaves one behind that was not paid for.
        $this->assertSame($before, Job::where('company_id', $company->id)->count());
    }

    private function jobPayload(int $n): array
    {
        return [
            'job_category_id' => JobCategory::firstOrFail()->id,
            'title' => 'Site Electrician '.$n,
            'country_code' => \App\Models\Country::where('is_active', true)->firstOrFail()->code,
            'location' => 'Singapore',
            'job_type' => 'Full Time',
            'work_mode' => 'On Site',
            'openings' => 1,
            'description' => 'Wiring and maintenance on a live site.',
        ];
    }
}
