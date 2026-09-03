<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FeatureFlag;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Package;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Offer letters carry a salary figure and a person's name out of the building.
 *
 * Which is why the facts are taken from our own records rather than from the
 * request body, and why the letter is always a draft. These tests hold both.
 */
class RecruitmentLetterTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private function setUpEmployer(bool $withAi = true): array
    {
        Cache::flush();

        foreach (['employer', 'job-seeker'] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug), 'guard_name' => 'web']);
        }

        foreach ([
            'platform_ai_enabled' => 'Platform AI',
            'ai_offer_letter_enabled' => 'AI Offer Letter',
            'ai_interview_letter_enabled' => 'AI Interview Letter',
            'ai_email_generator_enabled' => 'AI Email',
        ] as $key => $name) {
            FeatureFlag::updateOrCreate(['key' => $key], ['name' => $name, 'is_enabled' => true]);
        }

        $employer = User::create([
            'name' => 'Hiring Manager',
            'email' => 'hm'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $employer->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $this->company = Company::create([
            'name' => 'Tuas Port Logistics',
            'country_code' => 'SG',
            'status' => 'verified',
        ]);
        $this->company->users()->attach($employer->id, ['company_role' => 'company-admin', 'is_active' => true]);

        $package = Package::create([
            'name' => 'Plan',
            'slug' => 'plan-'.uniqid(),
            'validity_days' => 30,
            'entitlements' => ['ai_matching' => $withAi],
            'is_active' => true,
        ]);

        Subscription::create([
            'company_id' => $this->company->id,
            'package_id' => $package->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'entitlements' => ['ai_matching' => $withAi],
        ]);

        $job = Job::create([
            'company_id' => $this->company->id,
            'title' => 'Forklift Driver',
            'description' => 'Move pallets.',
            'country_code' => 'SG',
            'location' => 'Tuas',
            'currency_code' => 'SGD',
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);

        $candidate = User::create([
            'name' => 'Ravi Kumar',
            'email' => 'ravi'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $candidate->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        $application = JobApplication::create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'source' => 'website',
            'status' => 'Applied',
            'applied_at' => now()->subDay(),
        ]);

        return [$employer, $application];
    }

    public function test_an_offer_letter_uses_our_records_not_the_request_body(): void
    {
        [$employer, $application] = $this->setUpEmployer(withAi: false);
        Sanctum::actingAs($employer, ['employer']);

        $response = $this->postJson('/api/v1/employer/ai/letter', [
            'type' => 'offer',
            'application_id' => $application->id,
            'salary' => 2400,
            'start_date' => '1 October 2026',
        ]);

        $response->assertOk()->assertJsonPath('provider', 'local_template');

        $facts = $response->json('data.facts');

        // The name, job and company come from the database. A client that could
        // supply them could address somebody else's offer to a candidate of its
        // choosing, carrying a real salary out with it.
        $this->assertSame('Ravi Kumar', $facts['candidate_name']);
        $this->assertSame('Forklift Driver', $facts['job_title']);
        $this->assertSame('Tuas Port Logistics', $facts['company_name']);

        $body = $response->json('data.body');
        $this->assertStringContainsString('Ravi Kumar', $body);
        $this->assertStringContainsString('2400', $body);
        $this->assertStringContainsString('1 October 2026', $body);
    }

    public function test_a_plan_without_ai_still_gets_a_correct_letter(): void
    {
        [$employer, $application] = $this->setUpEmployer(withAi: false);
        Sanctum::actingAs($employer, ['employer']);

        // Not blocked from hiring. The letter is less polished and entirely
        // correct, which is the right way round.
        $this->postJson('/api/v1/employer/ai/letter', [
            'type' => 'interview',
            'application_id' => $application->id,
            'interview_at' => 'Tuesday 9 September, 10:00am',
        ])
            ->assertOk()
            ->assertJsonPath('ai', false)
            ->assertJsonPath('upgrade_required', true)
            ->assertSee('Tuesday 9 September');
    }

    public function test_the_admin_switch_for_offer_letters_is_honoured(): void
    {
        [$employer, $application] = $this->setUpEmployer(withAi: true);
        config(['services.gemini.api_key' => 'test-key']);

        // Spec §3 gives each letter type its own switch so an admin can run the
        // chatbot while leaving letter generation off.
        FeatureFlag::where('key', 'ai_offer_letter_enabled')->update(['is_enabled' => false]);
        Cache::flush();

        Sanctum::actingAs($employer, ['employer']);

        $this->postJson('/api/v1/employer/ai/letter', [
            'type' => 'offer',
            'application_id' => $application->id,
            'salary' => 2400,
        ])
            ->assertOk()
            ->assertJsonPath('provider', 'local_template')
            ->assertJsonPath('ai', false);
    }

    public function test_an_employer_cannot_draft_a_letter_for_another_companys_applicant(): void
    {
        [, $application] = $this->setUpEmployer();

        $rival = User::create([
            'name' => 'Rival',
            'email' => 'rival'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $rival->roles()->attach(Role::where('slug', 'employer')->value('id'));
        $rivalCo = Company::create(['name' => 'Rival Co', 'country_code' => 'SG', 'status' => 'verified']);
        $rivalCo->users()->attach($rival->id, ['company_role' => 'company-admin', 'is_active' => true]);

        Sanctum::actingAs($rival, ['employer']);

        // Guessing an id must not read back a competitor's candidate name.
        $this->postJson('/api/v1/employer/ai/letter', [
            'type' => 'offer',
            'application_id' => $application->id,
            'salary' => 9999,
        ])->assertNotFound();
    }

    public function test_a_rejection_email_says_so_plainly(): void
    {
        [$employer, $application] = $this->setUpEmployer(withAi: false);
        Sanctum::actingAs($employer, ['employer']);

        $body = $this->postJson('/api/v1/employer/ai/letter', [
            'type' => 'status',
            'application_id' => $application->id,
            'decision' => 'rejected',
        ])->assertOk()->json('data.body');

        $this->assertStringContainsString('other candidates', $body);
        $this->assertStringContainsString('Ravi Kumar', $body);
    }

    public function test_an_unauthenticated_caller_is_refused(): void
    {
        [, $application] = $this->setUpEmployer();

        $this->postJson('/api/v1/employer/ai/letter', [
            'type' => 'offer',
            'application_id' => $application->id,
        ])->assertUnauthorized();
    }
}
