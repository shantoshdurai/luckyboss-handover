<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Posting a vacancy is the employer portal's whole purpose, and it returned
 * HTTP 500 on every call: the controller referenced App\Models\Category, which
 * does not exist. It had never worked once.
 *
 * Three more faults sat behind that error, each of which would have surfaced
 * the moment it was fixed. These tests pin all four.
 */
class EmployerJobPostingTest extends TestCase
{
    use RefreshDatabase;

    private function employer(bool $withCompany = true): User
    {
        Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Hiring Manager',
            'email' => 'hm'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::where('slug', 'employer')->value('id'));

        if ($withCompany) {
            $company = Company::create([
                'name' => 'Tuas Port Logistics',
                'country_code' => 'SG',
                'status' => 'verified',
            ]);
            $company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);
        }

        return $user;
    }

    public function test_an_employer_can_post_a_vacancy(): void
    {
        Sanctum::actingAs($this->employer(), ['employer']);
        JobCategory::create(['name' => 'Warehouse', 'slug' => 'warehouse']);

        $response = $this->postJson('/api/v1/employer/jobs', [
            'title' => 'Night Shift Forklift Driver',
            'category' => 'Warehouse',
            'location' => 'Tuas',
            'salary_min' => 2200,
            'salary_max' => 2800,
            'description' => 'Move pallets on the night shift.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('job.title', 'Night Shift Forklift Driver')
            ->assertJsonPath('job.company', 'Tuas Port Logistics')
            // Matched on what the employer chose. Falling back to the first
            // category in the table filed warehouse work under whatever
            // happened to be seeded first, and the feed is browsed by category.
            ->assertJsonPath('job.category', 'Warehouse');

        $this->assertSame(1, Job::where('title', 'Night Shift Forklift Driver')->count());
    }

    public function test_a_missing_salary_is_left_empty_not_invented(): void
    {
        Sanctum::actingAs($this->employer(), ['employer']);

        $response = $this->postJson('/api/v1/employer/jobs', [
            'title' => 'General Helper',
            'description' => 'Site cleaning.',
        ]);

        $response->assertCreated();

        $job = Job::where('title', 'General Helper')->firstOrFail();

        // A salary of 4,000-7,000 and a location of Singapore used to be
        // invented here. Candidates would have applied for money no employer
        // had offered.
        $this->assertNull($job->salary_min);
        $this->assertNull($job->salary_max);
        $this->assertNull($job->location);
        $this->assertFalse((bool) $job->salary_visible);
    }

    public function test_an_account_with_no_company_is_refused_not_reassigned(): void
    {
        $orphan = $this->employer(withCompany: false);

        // Somebody else's company, which the old fallback would have used.
        Company::create(['name' => 'Another Business', 'country_code' => 'SG', 'status' => 'verified']);

        Sanctum::actingAs($orphan, ['employer']);

        $this->postJson('/api/v1/employer/jobs', ['title' => 'Orphan vacancy'])
            ->assertStatus(422);

        // Publishing under whichever company was first in the table put one
        // business's vacancy on another's profile.
        $this->assertSame(0, Job::count());
    }

    public function test_posting_requires_a_signed_in_employer(): void
    {
        // Both routes sat outside the auth group, so anyone could publish a
        // vacancy with no token at all.
        $this->postJson('/api/v1/jobs', ['title' => 'Spam vacancy'])
            ->assertUnauthorized();

        $this->postJson('/api/v1/employer/jobs', ['title' => 'Spam vacancy'])
            ->assertUnauthorized();

        $this->assertSame(0, Job::count());
    }

    public function test_a_job_seeker_cannot_post_a_vacancy(): void
    {
        Role::firstOrCreate(['slug' => 'job-seeker'], ['name' => 'Job Seeker', 'guard_name' => 'web']);

        $candidate = User::create([
            'name' => 'Ravi',
            'email' => 'ravi'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $candidate->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        Sanctum::actingAs($candidate, ['job-seeker']);

        $this->postJson('/api/v1/employer/jobs', ['title' => 'Not allowed'])
            ->assertForbidden();
    }

    public function test_a_maximum_salary_below_the_minimum_is_refused(): void
    {
        Sanctum::actingAs($this->employer(), ['employer']);

        $this->postJson('/api/v1/employer/jobs', [
            'title' => 'Backwards pay',
            'salary_min' => 3000,
            'salary_max' => 1000,
        ])->assertStatus(422);
    }
}
