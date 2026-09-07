<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\AiUsage;
use App\Models\Company;
use App\Models\EntitlementLedger;
use App\Models\Job;
use App\Models\Package;
use App\Models\User;
use App\Services\AiUsageRecorder;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Packages turning into balances (§33), expiry behaviour (§38), and AI cost
 * accounting (§67).
 *
 * The through-line: none of this may change anything for anyone until charging
 * is deliberately switched on. Every test that touches enforcement checks both
 * states, because the dangerous failure here is not "billing does not work" —
 * it is "billing works, on a live platform where nobody has bought anything".
 */
class PackageEntitlementTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SubscriptionEntitlementService
    {
        return app(SubscriptionEntitlementService::class);
    }

    private function enforce(bool $on): void
    {
        AdminRecord::updateOrCreate(
            ['module' => 'billing', 'slug' => 'entitlements'],
            ['name' => 'Entitlement enforcement', 'payload' => ['enforcement_enabled' => $on], 'is_active' => true]
        );
    }

    private function subscribe(Company $company, string $packageName, ?string $expires = null): void
    {
        $package = Package::where('name', $packageName)->firstOrFail();

        $company->subscriptions()->create([
            'package_id' => $package->id,
            'status' => 'active',
            'starts_at' => today()->subDay(),
            'expires_at' => $expires ?? today()->addMonth(),
            'entitlements' => $package->entitlements,
            'currency_code' => 'SGD',
            'amount' => 299,
        ]);
    }

    // ── Packages → balances (§33) ──────────────────────────────────────────

    public function test_a_package_allowance_becomes_a_real_balance(): void
    {
        $this->seed();
        $company = Company::create(['name' => 'Tuas Port Logistics', 'country_code' => 'SGP']);
        $this->subscribe($company, 'Professional');

        // Professional grants 25 job posts (§33), on top of the 1 free-tier credit.
        $this->assertSame(26, $this->service()->balance($company, 'job_post'));

        // §71: Professional is 250 contact views. Plus the 5 free-tier.
        $this->assertSame(255, $this->service()->balance($company, 'candidate_view'));
    }

    public function test_the_plan_allowance_is_issued_once_per_month(): void
    {
        $this->seed();
        $company = Company::create(['name' => 'Jurong Freight', 'country_code' => 'SGP']);
        $this->subscribe($company, 'Professional');

        $this->service()->balance($company, 'job_post');
        $this->service()->balance($company, 'job_post');
        $this->service()->balance($company, 'job_post');

        $this->assertSame(1, EntitlementLedger::where('owner_id', $company->id)
            ->where('key', 'job_post')->where('source', 'plan_grant')->count());
    }

    public function test_a_plan_allowance_expires_but_a_bought_pack_does_not(): void
    {
        $this->seed();
        $company = Company::create(['name' => 'Keppel Civil', 'country_code' => 'SGP']);
        $this->subscribe($company, 'Professional');

        $this->service()->balance($company, 'job_post');
        $this->service()->grant($company, 'job_post', 10);

        // This is the whole difference between the two billing models sir may
        // choose between, and it is one nullable column.
        $this->assertNotNull(EntitlementLedger::where('owner_id', $company->id)->where('source', 'plan_grant')->firstOrFail()->expires_at);
        $this->assertNull(EntitlementLedger::where('owner_id', $company->id)->where('source', 'admin_grant')->firstOrFail()->expires_at);
    }

    public function test_an_unlimited_package_reports_unlimited_not_a_big_number(): void
    {
        $this->seed();
        $company = Company::create(['name' => 'Enterprise Co', 'country_code' => 'SGP']);
        $this->subscribe($company, 'Enterprise');

        // Enterprise stores -1. It must surface as null, because no finite total
        // can represent unlimited and a large integer eventually runs out.
        $this->assertNull($this->service()->balance($company, 'job_post'));
        $this->assertNull($this->service()->balance($company, 'candidate_view'));
    }

    public function test_an_unlimited_package_is_never_refused_even_with_charging_on(): void
    {
        $this->seed();
        $this->enforce(true);
        $company = Company::create(['name' => 'Enterprise Co', 'country_code' => 'SGP']);
        $this->subscribe($company, 'Enterprise');

        $this->assertTrue($this->service()->consume($company, 'job_post', 5000));
    }

    public function test_a_lapsed_subscription_grants_nothing(): void
    {
        $this->seed();
        $company = Company::create(['name' => 'Lapsed Ltd', 'country_code' => 'SGP']);
        $this->subscribe($company, 'Professional', today()->subDay()->format('Y-m-d'));

        // Only the free tier remains — the plan is over.
        $this->assertSame(1, $this->service()->balance($company, 'job_post'));
    }

    public function test_every_market_has_its_own_stored_price(): void
    {
        $this->seed();

        // §64 rejects live conversion, so each market is a stored number.
        $professional = Package::where('name', 'Professional')->firstOrFail();

        $this->assertEqualsWithDelta(299, $professional->prices()->where('currency_code', 'SGD')->value('amount'), 0.01);
        $this->assertEqualsWithDelta(18000, $professional->prices()->where('currency_code', 'INR')->value('amount'), 0.01);
        $this->assertEqualsWithDelta(999, $professional->prices()->where('currency_code', 'MYR')->value('amount'), 0.01);
    }

    // ── Expiry behaviour (§38) ─────────────────────────────────────────────

    public function test_an_expired_employer_is_not_touched_while_charging_is_off(): void
    {
        $this->seed();
        $this->enforce(false);

        $employer = User::where('email', 'employer@luckyboss.test')->firstOrFail();
        $employer->companies()->first()->subscriptions()->update(['expires_at' => today()->subMonth()]);
        $job = Job::where('company_id', $employer->companies()->first()->id)->firstOrFail();

        // The dangerous failure: shipping expiry enforcement onto a live platform
        // where no employer has ever paid, and locking them all out at once.
        $this->actingAs($employer)->get(route('employer.jobs.applicants', $job))->assertOk();
    }

    public function test_with_charging_on_an_expired_employer_loses_the_recruitment_surface(): void
    {
        $this->seed();
        $this->enforce(true);

        $employer = User::where('email', 'employer@luckyboss.test')->firstOrFail();
        $employer->companies()->first()->subscriptions()->update(['expires_at' => today()->subMonth()]);
        $job = Job::where('company_id', $employer->companies()->first()->id)->firstOrFail();

        $this->actingAs($employer)
            ->get(route('employer.jobs.applicants', $job))
            ->assertRedirect(route('employer.subscription'));
    }

    public function test_an_expired_employer_can_still_reach_billing_and_renew(): void
    {
        $this->seed();
        $this->enforce(true);

        $employer = User::where('email', 'employer@luckyboss.test')->firstOrFail();
        $employer->companies()->first()->subscriptions()->update(['expires_at' => today()->subMonth()]);

        // §38 is explicit: login stays, and so does everything needed to pay us.
        // A customer locked out entirely churns; one who can see what they are
        // missing renews.
        $this->actingAs($employer)->get(route('employer.subscription'))->assertOk();
        $this->actingAs($employer)->get(route('employer.dashboard'))->assertOk();
    }

    // ── AI cost accounting (§67) ───────────────────────────────────────────

    public function test_an_ai_call_records_its_tokens_and_estimated_cost(): void
    {
        $this->seed();
        $employer = User::where('email', 'employer@luckyboss.test')->firstOrFail();

        app(AiUsageRecorder::class)->record(
            feature: 'ai_chat',
            user: $employer,
            model: 'gemini-2.5-flash',
            promptTokens: 1_000_000,
            completionTokens: 1_000_000,
        );

        $row = AiUsage::latest('id')->firstOrFail();

        $this->assertSame('gemini-2.5-flash', $row->model);
        $this->assertSame(1_000_000, $row->prompt_tokens);
        // 1M in at $0.30 + 1M out at $2.50.
        $this->assertEqualsWithDelta(2.80, (float) $row->estimated_cost_usd, 0.000001);
        $this->assertNotNull($row->company_id, 'spend must be attributable to a company, or it cannot be priced');
    }

    public function test_a_failed_ai_call_is_still_costed(): void
    {
        $this->seed();

        // The tokens were spent before we found out it failed. A log that counts
        // only successes understates what AI costs us.
        app(AiUsageRecorder::class)->record(
            feature: 'resume_parse',
            model: 'gemini-2.5-flash',
            promptTokens: 500_000,
            completionTokens: 0,
            status: 'failed',
        );

        $row = AiUsage::latest('id')->firstOrFail();

        $this->assertSame('failed', $row->status);
        $this->assertEqualsWithDelta(0.15, (float) $row->estimated_cost_usd, 0.000001);
    }

    public function test_byoai_spend_is_recorded_but_costs_us_nothing(): void
    {
        $this->seed();

        // §5: the employer paid their own provider. Counting it as our cost would
        // make our cheapest customers look like our most expensive.
        app(AiUsageRecorder::class)->record(
            feature: 'ai_match',
            model: 'gpt-4o-mini',
            promptTokens: 1_000_000,
            completionTokens: 1_000_000,
            provider: 'employer_byoai',
        );

        $row = AiUsage::latest('id')->firstOrFail();

        $this->assertSame('employer_byoai', $row->provider);
        $this->assertEqualsWithDelta(0.0, (float) $row->estimated_cost_usd, 0.000001);
    }

    public function test_an_unknown_model_is_not_silently_free(): void
    {
        $this->seed();

        app(AiUsageRecorder::class)->record(
            feature: 'ai_chat',
            model: 'some-model-we-added-later',
            promptTokens: 1_000_000,
            completionTokens: 0,
        );

        $this->assertGreaterThan(0, (float) AiUsage::latest('id')->firstOrFail()->estimated_cost_usd);
    }
}
