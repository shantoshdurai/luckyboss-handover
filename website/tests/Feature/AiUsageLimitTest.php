<?php

namespace Tests\Feature;

use App\Models\AiUsage;
use App\Models\Company;
use App\Models\FeatureFlag;
use App\Models\Package;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Every AI call costs real money, and the packages said only yes or no.
 *
 * Spec §33 lists "AI Usage" beside the job and candidate limits, and it was the
 * only one with nothing behind it — so one employer on a plan that felt
 * unlimited could spend more of the Gemini budget in a month than their whole
 * subscription brings in, and nobody would find out until the bill.
 */
class AiUsageLimitTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private function employerWithAllowance(?int $allowance): User
    {
        Cache::flush();
        Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer', 'guard_name' => 'web']);

        foreach (['platform_ai_enabled' => 'Platform AI', 'employer_byoai_enabled' => 'BYOAI'] as $k => $n) {
            FeatureFlag::updateOrCreate(['key' => $k], ['name' => $n, 'is_enabled' => $k === 'platform_ai_enabled']);
        }

        $user = User::create([
            'name' => 'Hiring Manager',
            'email' => 'hm'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $this->company = Company::create([
            'name' => 'Tuas Port Logistics',
            'country_code' => 'SG',
            'status' => 'verified',
        ]);
        $this->company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);

        $entitlements = ['ai_matching' => true];
        if ($allowance !== null) {
            $entitlements['ai_usage'] = $allowance;
        }

        $package = Package::create([
            'name' => 'Plan',
            'slug' => 'plan-'.uniqid(),
            'validity_days' => 30,
            'entitlements' => $entitlements,
            'is_active' => true,
        ]);

        Subscription::create([
            'company_id' => $this->company->id,
            'package_id' => $package->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'entitlements' => $entitlements,
        ]);

        return $user;
    }

    private function fakeGemini(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'summary' => 'Drive a forklift on the night shift.',
                    'responsibilities' => ['Move pallets', 'Keep the yard tidy'],
                    'requirements' => ['Forklift licence', 'Night availability'],
                ])]]]]],
            ], 200),
        ]);
    }

    public function test_a_used_up_allowance_stops_further_ai_calls(): void
    {
        Sanctum::actingAs($this->employerWithAllowance(2), ['employer']);
        $this->fakeGemini();

        foreach ([1, 2] as $n) {
            $this->postJson('/api/v1/employer/ai/job-description', ['title' => "Driver {$n}"])
                ->assertOk()
                ->assertJsonPath('ai', true);
        }

        $this->assertSame(2, AiUsage::where('company_id', $this->company->id)->count());

        // The third is refused and told why, in words a customer can act on.
        $third = $this->postJson('/api/v1/employer/ai/job-description', ['title' => 'Driver 3']);

        $third->assertOk()
            ->assertJsonPath('ai', false)
            ->assertJsonPath('upgrade_required', true);

        $this->assertStringContainsString('used all 2 AI actions', $third->json('message'));

        // Not blocked from working — they still get a usable draft.
        $this->assertNotEmpty($third->json('data.summary'));

        // And the refused call did not consume anything further.
        $this->assertSame(2, AiUsage::where('company_id', $this->company->id)->count());
    }

    public function test_a_template_fallback_does_not_consume_the_allowance(): void
    {
        Sanctum::actingAs($this->employerWithAllowance(5), ['employer']);

        // No API key configured, so the engine takes the template path and we
        // spend nothing. Charging for that would be taking something for free.
        config(['services.gemini.api_key' => null]);

        $this->postJson('/api/v1/employer/ai/job-description', ['title' => 'General Helper'])
            ->assertOk();

        $this->assertSame(0, AiUsage::where('company_id', $this->company->id)->count());
    }

    public function test_a_plan_with_no_ai_usage_field_is_treated_as_unlimited(): void
    {
        Sanctum::actingAs($this->employerWithAllowance(null), ['employer']);
        $this->fakeGemini();

        // Silently capping an existing customer at zero because a field was not
        // filled in would be worse than the problem the limit solves.
        foreach (range(1, 3) as $n) {
            $this->postJson('/api/v1/employer/ai/job-description', ['title' => "Role {$n}"])
                ->assertOk()
                ->assertJsonPath('ai', true);
        }

        $this->getJson('/api/v1/employer/ai/status')
            ->assertOk()
            ->assertJsonPath('data.usage.unlimited', true)
            ->assertJsonPath('data.usage.used_this_month', 3);
    }

    public function test_unlimited_is_expressed_as_minus_one_like_the_other_limits(): void
    {
        Sanctum::actingAs($this->employerWithAllowance(-1), ['employer']);

        $this->getJson('/api/v1/employer/ai/status')
            ->assertOk()
            ->assertJsonPath('data.usage.unlimited', true)
            ->assertJsonPath('data.usage.monthly_limit', null);
    }

    public function test_the_status_endpoint_reports_what_is_left(): void
    {
        Sanctum::actingAs($this->employerWithAllowance(10), ['employer']);
        $this->fakeGemini();

        $this->postJson('/api/v1/employer/ai/job-description', ['title' => 'Packer'])->assertOk();

        $this->getJson('/api/v1/employer/ai/status')
            ->assertOk()
            ->assertJsonPath('data.usage.monthly_limit', 10)
            ->assertJsonPath('data.usage.used_this_month', 1)
            ->assertJsonPath('data.usage.remaining', 9);
    }

    public function test_last_months_usage_does_not_count_against_this_month(): void
    {
        Sanctum::actingAs($this->employerWithAllowance(2), ['employer']);

        AiUsage::create([
            'company_id' => $this->company->id,
            'feature' => 'job_description',
            'source' => 'platform',
        ]);

        // Backdated past the month boundary. The allowance is monthly, so this
        // must not be held against them.
        AiUsage::where('company_id', $this->company->id)
            ->update(['created_at' => now()->subMonth()->startOfMonth()]);

        $this->getJson('/api/v1/employer/ai/status')
            ->assertOk()
            ->assertJsonPath('data.usage.used_this_month', 0)
            ->assertJsonPath('data.usage.remaining', 2);
    }
}
