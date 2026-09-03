<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FeatureFlag;
use App\Models\Job;
use App\Models\JobBoost;
use App\Models\JobView;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Insights are only worth showing if the numbers are true.
 *
 * The employer app sold a boost and reported nothing about it. The temptation
 * when building that screen is to fill it with something plausible; these tests
 * exist to make that impossible to do quietly.
 */
class EmployerInsightsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private function employer(): User
    {
        Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer', 'guard_name' => 'web']);
        FeatureFlag::updateOrCreate(['key' => 'platform_ai_enabled'], ['name' => 'Platform AI', 'is_enabled' => true]);

        $user = User::create([
            'name' => 'Hiring Manager',
            'email' => 'ins'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $this->company = Company::create([
            'name' => 'Insights Co',
            'country_code' => 'SG',
            'status' => 'verified',
        ]);
        $this->company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);

        return $user;
    }

    private function job(): Job
    {
        return Job::create([
            'company_id' => $this->company->id,
            'title' => 'Warehouse Picker',
            'description' => 'Pick and pack orders.',
            'country_code' => 'SG',
            'status' => 'published',
            'published_at' => now()->subDays(30),
        ]);
    }

    public function test_a_candidate_refreshing_a_job_counts_as_one_view(): void
    {
        $employer = $this->employer();
        $job = $this->job();

        Role::firstOrCreate(['slug' => 'job-seeker'], ['name' => 'Job Seeker', 'guard_name' => 'web']);
        $candidate = User::create([
            'name' => 'Ravi',
            'email' => 'ravi'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $candidate->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        Sanctum::actingAs($candidate, ['job-seeker']);

        // Opening it, backing out to compare, and opening it again is one look.
        // Counting raw hits would let a boost appear several times more
        // effective than it was.
        $this->getJson("/api/v1/jobs/{$job->id}")->assertOk();
        $this->getJson("/api/v1/jobs/{$job->id}")->assertOk();
        $this->getJson("/api/v1/jobs/{$job->id}")->assertOk();

        $this->assertSame(1, JobView::where('job_id', $job->id)->count());

        Sanctum::actingAs($employer, ['employer']);
        $this->getJson("/api/v1/employer/jobs/{$job->id}/insights")
            ->assertOk()
            ->assertJsonPath('data.views', 1);
    }

    public function test_an_employer_viewing_their_own_vacancy_is_not_an_impression(): void
    {
        $employer = $this->employer();
        $job = $this->job();

        Sanctum::actingAs($employer, ['employer']);
        $this->getJson("/api/v1/jobs/{$job->id}")->assertOk();

        $this->assertSame(0, JobView::where('job_id', $job->id)->count(),
            'checking your own listing must not inflate what you are billed against');
    }

    public function test_apply_rate_is_null_rather_than_zero_when_nobody_has_looked(): void
    {
        Sanctum::actingAs($this->employer(), ['employer']);
        $job = $this->job();

        // "No views yet" and "nobody applied" are different facts, and a 0%
        // apply rate on a vacancy nobody has seen reads as a failing listing.
        $this->getJson("/api/v1/employer/jobs/{$job->id}/insights")
            ->assertOk()
            ->assertJsonPath('data.views', 0)
            ->assertJsonPath('data.apply_rate', null);
    }

    public function test_a_boost_reports_real_views_and_says_when_it_cannot_compare(): void
    {
        $employer = $this->employer();
        $job = $this->job();

        $boost = JobBoost::create([
            'job_id' => $job->id,
            'company_id' => $this->company->id,
            'type' => 'featured',
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addDays(4),
            'amount' => 4500,
            'currency' => 'SGD',
            'status' => 'active',
        ]);

        foreach (range(1, 4) as $i) {
            JobView::create([
                'job_id' => $job->id,
                'source' => 'app',
                'dedupe_hash' => 'during-'.$i,
                'viewed_at' => now()->subDays(2),
            ]);
        }

        Sanctum::actingAs($employer, ['employer']);
        $response = $this->getJson("/api/v1/employer/jobs/{$job->id}/insights");

        $response->assertOk()
            ->assertJsonPath('data.boost.views_during', 4)
            ->assertJsonPath('data.boost.active', true)
            ->assertJsonPath('data.boost.amount', 4500)
            // Published 30 days ago, so a like-for-like window before the boost
            // exists and the comparison is honest to show.
            ->assertJsonPath('data.boost.comparable', true)
            ->assertJsonPath('data.boost.views_before', 0);

        $this->assertSame('featured', $response->json('data.boost.type'));
        $this->assertNotNull($boost->fresh());
    }

    public function test_a_boost_on_a_brand_new_vacancy_refuses_to_compare(): void
    {
        $employer = $this->employer();

        $job = Job::create([
            'company_id' => $this->company->id,
            'title' => 'Same-day Driver',
            'description' => 'Deliveries.',
            'country_code' => 'SG',
            'status' => 'published',
            'published_at' => now()->subHours(6),
        ]);

        JobBoost::create([
            'job_id' => $job->id,
            'company_id' => $this->company->id,
            'type' => 'top',
            'starts_at' => now()->subHours(5),
            'ends_at' => now()->addDays(7),
            'amount' => 9000,
            'currency' => 'SGD',
            'status' => 'active',
        ]);

        Sanctum::actingAs($employer, ['employer']);

        // There is no "before" to measure against. Reporting a rise from a
        // window when the job did not exist would flatter the boost with a
        // number that means nothing.
        $this->getJson("/api/v1/employer/jobs/{$job->id}/insights")
            ->assertOk()
            ->assertJsonPath('data.boost.comparable', false)
            ->assertJsonPath('data.boost.views_before', null);
    }

    public function test_the_overview_reports_the_plan_and_real_totals(): void
    {
        Sanctum::actingAs($this->employer(), ['employer']);
        $this->job();

        $this->getJson('/api/v1/employer/insights')
            ->assertOk()
            ->assertJsonPath('data.company.verified', true)
            ->assertJsonPath('data.plan.active', false)
            ->assertJsonPath('data.totals.active_jobs', 1)
            ->assertJsonPath('data.totals.views', 0)
            ->assertJsonPath('data.boosts.active', 0);
    }

    public function test_one_employer_cannot_read_another_companys_insights(): void
    {
        $this->employer();
        $job = $this->job();

        $other = User::create([
            'name' => 'Rival',
            'email' => 'rival'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $other->roles()->attach(Role::where('slug', 'employer')->value('id'));
        $rivalCompany = Company::create(['name' => 'Rival Co', 'country_code' => 'SG', 'status' => 'verified']);
        $rivalCompany->users()->attach($other->id, ['company_role' => 'company-admin', 'is_active' => true]);

        Sanctum::actingAs($other, ['employer']);

        $this->getJson("/api/v1/employer/jobs/{$job->id}/insights")->assertNotFound();
    }
}
