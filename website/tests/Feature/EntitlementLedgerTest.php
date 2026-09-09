<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\Company;
use App\Models\EntitlementLedger;
use App\Models\Role;
use App\Models\User;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The entitlement ledger.
 *
 * Sir's decision on 2026-09-07 was that employers are charged and seekers are
 * free — but free as **zero-priced SKUs, not a hardcoded exemption**, so the
 * backend can move seekers onto paid at any time. These tests pin the three
 * properties that make that promise real:
 *
 *  1. Seekers go through the same path as employers and are **metered even
 *     though they are free**, because a later pricing decision needs history.
 *  2. **Unlimited is null**, never a large number, so nobody hits an invisible
 *     ceiling.
 *  3. **Metering and enforcement are separate switches.** With enforcement off —
 *     the default — nothing is ever refused, but everything is still recorded.
 */
class EntitlementLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SubscriptionEntitlementService
    {
        return app(SubscriptionEntitlementService::class);
    }

    private function company(): Company
    {
        return Company::create(['name' => 'Tuas Port Logistics', 'country_code' => 'SGP']);
    }

    private function seeker(): User
    {
        Role::firstOrCreate(['slug' => 'job-seeker'], ['name' => 'Job Seeker', 'guard_name' => 'web']);
        $user = User::create(['name' => 'Ravi', 'email' => 'ravi'.uniqid().'@example.com', 'password' => 'password']);
        $user->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        return $user;
    }

    private function enforce(bool $on): void
    {
        AdminRecord::updateOrCreate(
            ['module' => 'billing', 'slug' => 'entitlements'],
            ['name' => 'Entitlement enforcement', 'payload' => ['enforcement_enabled' => $on], 'is_active' => true]
        );
    }

    public function test_a_seeker_action_is_unlimited_but_still_recorded(): void
    {
        $user = $this->seeker();

        $this->assertNull($this->service()->balance($user, 'apply'), 'unlimited must be null, never a large integer');

        $this->assertTrue($this->service()->consume($user, 'apply', 1));

        // The whole point of metering something free: when sir asks what it
        // would cost to price this, the answer already exists.
        $this->assertDatabaseHas('entitlement_ledger', [
            'owner_id' => $user->id,
            'key' => 'apply',
            'delta' => -1,
            'source' => 'consumption',
        ]);
    }

    public function test_a_free_seeker_action_is_never_refused_even_with_enforcement_on(): void
    {
        $this->enforce(true);
        $user = $this->seeker();

        for ($i = 0; $i < 50; $i++) {
            $this->assertTrue($this->service()->consume($user, 'apply', 1), 'an unlimited SKU must never refuse');
        }
    }

    public function test_an_employer_gets_this_months_free_tier_once(): void
    {
        $company = $this->company();

        $this->assertSame(1, $this->service()->balance($company, 'job_post'));

        // Reading the balance repeatedly must not keep re-issuing the allowance.
        $this->service()->balance($company, 'job_post');
        $this->service()->balance($company, 'job_post');

        $this->assertSame(1, EntitlementLedger::where('owner_id', $company->id)
            ->where('key', 'job_post')->where('source', 'free_tier')->count());
    }

    public function test_consumption_reduces_the_balance(): void
    {
        $company = $this->company();
        $this->service()->grant($company, 'job_post', 4);

        $this->assertSame(5, $this->service()->balance($company, 'job_post'), '4 granted + 1 free tier');

        $this->service()->consume($company, 'job_post', 2);

        $this->assertSame(3, $this->service()->balance($company, 'job_post'));
    }

    public function test_with_enforcement_off_a_shortfall_is_recorded_but_allowed(): void
    {
        $this->enforce(false);
        $company = $this->company();

        // Only the single free-tier credit exists; ask for far more.
        $this->assertTrue($this->service()->consume($company, 'job_post', 10));

        $this->assertSame(-9, $this->service()->balance($company, 'job_post'));
    }

    public function test_with_enforcement_on_a_shortfall_is_refused_and_nothing_is_taken(): void
    {
        $this->enforce(true);
        $company = $this->company();

        $before = $this->service()->balance($company, 'job_post');

        $this->assertFalse($this->service()->consume($company, 'job_post', 10));

        // All or nothing: a half-charged bulk action is worse than a refusal.
        $this->assertSame($before, $this->service()->balance($company, 'job_post'));
        $this->assertDatabaseMissing('entitlement_ledger', [
            'owner_id' => $company->id,
            'key' => 'job_post',
            'source' => 'consumption',
        ]);
    }

    public function test_free_tier_expires_with_the_month_but_a_grant_does_not(): void
    {
        $company = $this->company();
        $this->service()->balance($company, 'job_post');
        $this->service()->grant($company, 'job_post', 3);

        $freeTier = EntitlementLedger::where('owner_id', $company->id)->where('source', 'free_tier')->firstOrFail();
        $granted = EntitlementLedger::where('owner_id', $company->id)->where('source', 'admin_grant')->firstOrFail();

        // This difference is the only thing separating a recurring plan's
        // monthly allowance from a purchased pack.
        $this->assertNotNull($freeTier->expires_at);
        $this->assertNull($granted->expires_at);
    }

    public function test_expired_grants_do_not_count_towards_the_balance(): void
    {
        $company = $this->company();
        $this->service()->grant($company, 'job_post', 20, 'plan_grant', now()->subDay());

        $this->assertSame(1, $this->service()->balance($company, 'job_post'), 'only the live free-tier credit should count');
    }

    public function test_the_summary_is_shaped_for_a_name_remaining_table(): void
    {
        $summary = $this->service()->summary($this->company(), 'employer');

        $this->assertArrayHasKey('job_post', $summary);
        $this->assertSame('Post a vacancy', $summary['job_post']['label']);
        $this->assertSame(1, $summary['job_post']['remaining']);
        $this->assertFalse($summary['job_post']['unlimited']);
    }

    public function test_the_original_feature_gate_still_works(): void
    {
        $company = $this->company();
        $package = \App\Models\Package::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'validity_days' => 30,
            'entitlements' => ['byoai' => true],
            'is_active' => true,
        ]);

        $company->subscriptions()->create([
            'package_id' => $package->id,
            'status' => 'active',
            'starts_at' => today()->subDay(),
            'expires_at' => today()->addMonth(),
            'entitlements' => ['byoai' => true],
        ]);

        $this->assertTrue($this->service()->allows($company, 'byoai'));
        $this->assertFalse($this->service()->allows($company, 'something_else'));
    }
}
