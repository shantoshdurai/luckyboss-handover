<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Package;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * What an employer sees about a candidate must be what the candidate entered.
 *
 * This endpoint used to substitute a default for every missing field — a phone
 * number of "+65 8000 0000", an email of "applicant@example.com", three years
 * of experience, a location of Singapore and an AI match score of 85. An
 * employer saw a complete, confident profile of somebody who had filled in
 * almost none of it, and would have dialled a number belonging to nobody.
 */
class EmployerCandidateListTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private function employerOnPlan(array $entitlements): User
    {
        foreach (['employer', 'job-seeker'] as $slug) {
            Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug), 'guard_name' => 'web']);
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

        return $employer;
    }

    /** A candidate who applied having filled in almost nothing. */
    private function sparseApplicant(): JobApplication
    {
        $job = Job::create([
            'company_id' => $this->company->id,
            'title' => 'Forklift Driver',
            'description' => 'Move pallets.',
            'country_code' => 'SG',
            'status' => 'published',
            'published_at' => now()->subDays(3),
        ]);

        $candidate = User::create([
            'name' => 'Ravi Kumar',
            'email' => 'ravi'.uniqid().'@example.com',
            'phone' => '+6591230000',
            'password' => 'password',
        ]);
        $candidate->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));
        CandidateProfile::create(['user_id' => $candidate->id, 'country_code' => 'SG']);

        return JobApplication::create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'source' => 'website',
            'status' => 'Applied',
            'applied_at' => now()->subDay(),
        ]);
    }

    public function test_an_empty_profile_is_reported_empty_not_filled_in_for_them(): void
    {
        Sanctum::actingAs($this->employerOnPlan(['candidate_views' => 50]), ['employer']);
        $this->sparseApplicant();

        $candidate = $this->getJson('/api/v1/employer/candidates')
            ->assertOk()
            ->json('candidates.0');

        // Every one of these used to arrive with a confident invented value.
        $this->assertNull($candidate['years_experience'], 'three years of experience used to be invented here');
        $this->assertNull($candidate['location'], 'a location of "Singapore" used to be invented here');
        $this->assertNull($candidate['headline']);
        $this->assertSame([], $candidate['skills'], 'skills of ["General"] used to be invented here');

        // The most persuasive number on the screen, and it was made up.
        $this->assertNull($candidate['ai_match_score'], 'a match score of 85 used to be invented here');
    }

    public function test_real_details_are_returned_when_the_candidate_provided_them(): void
    {
        Sanctum::actingAs($this->employerOnPlan(['candidate_views' => 50]), ['employer']);
        $application = $this->sparseApplicant();

        $application->candidate->candidateProfile->update([
            'years_experience' => 6,
            'current_location' => 'Chennai',
            'headline' => 'Lorry driver, Class 4',
            'skills' => ['Heavy Vehicle', 'Route Planning'],
        ]);
        $application->update(['match_score' => 72]);

        $candidate = $this->getJson('/api/v1/employer/candidates')->json('candidates.0');

        $this->assertSame(6, $candidate['years_experience']);
        $this->assertSame('Chennai', $candidate['location']);
        // JSON returns 72.0 as 72; the value is what matters, not the PHP type.
        $this->assertEquals(72, $candidate['ai_match_score']);
        $this->assertSame('Heavy Vehicle', $candidate['skills'][0]);
    }

    public function test_contact_details_are_withheld_from_a_plan_that_does_not_include_them(): void
    {
        // Spec §14: phone and email are shown based on package permission.
        // contact_revealed was hardcoded true, so every plan saw every number
        // and the contact limits sold in the packages meant nothing.
        Sanctum::actingAs($this->employerOnPlan(['candidate_views' => 0]), ['employer']);
        $this->sparseApplicant();

        $candidate = $this->getJson('/api/v1/employer/candidates')->json('candidates.0');

        $this->assertFalse($candidate['contact_revealed']);
        $this->assertNull($candidate['candidate_phone']);
        $this->assertNull($candidate['candidate_email']);

        // The candidate is still listed — the employer can see who applied,
        // they simply cannot call them without the right plan.
        $this->assertSame('Ravi Kumar', $candidate['candidate_name']);
    }

    public function test_a_plan_with_contact_access_sees_the_real_number(): void
    {
        Sanctum::actingAs($this->employerOnPlan(['candidate_views' => 500]), ['employer']);
        $this->sparseApplicant();

        $candidate = $this->getJson('/api/v1/employer/candidates')->json('candidates.0');

        $this->assertTrue($candidate['contact_revealed']);
        $this->assertSame('+6591230000', $candidate['candidate_phone']);
    }

    public function test_the_real_application_id_is_returned_so_the_portal_can_act_on_it(): void
    {
        Sanctum::actingAs($this->employerOnPlan(['candidate_views' => 50]), ['employer']);
        $application = $this->sparseApplicant();

        $candidate = $this->getJson('/api/v1/employer/candidates')->json('candidates.0');

        // Drafting a letter or moving a stage needs the real id, not one parsed
        // back out of a display string like "cand-12".
        $this->assertSame($application->id, $candidate['application_id']);
    }
}
