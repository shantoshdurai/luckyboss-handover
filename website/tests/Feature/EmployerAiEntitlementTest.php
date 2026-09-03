<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FeatureFlag;
use App\Models\Package;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The employer subscription is what pays for the AI, so the plan has to decide
 * who gets it — on the server.
 *
 * Spec section 93: "Do not only hide an AI button in Flutter... Even if someone
 * manually calls /api/generate-offer-ai, Laravel must reject the request."
 * These tests call the endpoints directly, the way that warning describes.
 */
class EmployerAiEntitlementTest extends TestCase
{
    use RefreshDatabase;

    private function employerOnPlan(array $entitlements): User
    {
        Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Hiring Manager',
            'email' => 'hm'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $company = Company::create([
            'name' => 'Test Co '.uniqid(),
            'country_code' => 'SG',
            'status' => 'verified',
        ]);
        $company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);

        $package = Package::create([
            'name' => 'Test Plan',
            'slug' => 'test-plan-'.uniqid(),
            'validity_days' => 30,
            'entitlements' => $entitlements,
            'is_active' => true,
        ]);

        Subscription::create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'entitlements' => $entitlements,
        ]);

        FeatureFlag::updateOrCreate(['key' => 'platform_ai_enabled'], ['name' => 'Platform AI', 'is_enabled' => true]);
        FeatureFlag::updateOrCreate(['key' => 'employer_byoai_enabled'], ['name' => 'Employer BYOAI', 'is_enabled' => false]);

        return $user;
    }

    public function test_a_plan_with_ai_gets_ai(): void
    {
        Sanctum::actingAs($this->employerOnPlan(['ai_matching' => true]), ['employer']);

        $this->getJson('/api/v1/employer/ai/status')
            ->assertOk()
            ->assertJsonPath('data.ai_available', true)
            ->assertJsonPath('data.upgrade_required', false)
            ->assertJsonPath('data.features.job_description', true);
    }

    public function test_a_plan_without_ai_is_told_to_upgrade(): void
    {
        Sanctum::actingAs($this->employerOnPlan(['ai_matching' => false]), ['employer']);

        $this->getJson('/api/v1/employer/ai/status')
            ->assertOk()
            ->assertJsonPath('data.ai_available', false)
            ->assertJsonPath('data.upgrade_required', true)
            ->assertJsonPath('data.features.job_description', false);
    }

    public function test_an_unentitled_employer_still_gets_a_usable_draft_but_not_from_ai(): void
    {
        Sanctum::actingAs($this->employerOnPlan(['ai_matching' => false]), ['employer']);

        $response = $this->postJson('/api/v1/employer/ai/job-description', [
            'title' => 'Forklift Driver',
            'location' => 'Chennai',
        ]);

        // The point of the fallback: the employer is not blocked from posting a
        // job, they simply do not get the AI they did not pay for.
        $response->assertOk()
            ->assertJsonPath('ai', false)
            ->assertJsonPath('upgrade_required', true)
            ->assertJsonPath('provider', 'local_heuristic_rule_engine');

        $this->assertNotEmpty($response->json('data.summary'));
        $this->assertNotEmpty($response->json('data.responsibilities'));
    }

    public function test_the_admin_master_switch_overrides_a_paid_plan(): void
    {
        Sanctum::actingAs($this->employerOnPlan(['ai_matching' => true]), ['employer']);

        // An admin switching AI off mid-incident must win over any entitlement.
        FeatureFlag::updateOrCreate(['key' => 'platform_ai_enabled'], ['name' => 'Platform AI', 'is_enabled' => false]);

        $this->getJson('/api/v1/employer/ai/status')
            ->assertOk()
            ->assertJsonPath('data.ai_available', false)
            // Not an upgrade problem — upselling here would be a lie.
            ->assertJsonPath('data.upgrade_required', false);
    }

    public function test_the_endpoints_reject_an_unauthenticated_caller(): void
    {
        $this->getJson('/api/v1/employer/ai/status')->assertUnauthorized();
        $this->postJson('/api/v1/employer/ai/job-description', ['title' => 'X'])->assertUnauthorized();
    }
}
