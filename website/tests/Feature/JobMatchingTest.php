<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\JobMatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Matching, and the rule the whole feature rests on: we do not invent a score.
 *
 * The behaviour these lock in was a real defect, not a hypothetical. The seeker
 * dashboard listed every published vacancy under "Curated ... based on your
 * location and background", and the scorer behind it clamped every result to a
 * minimum of 45%, so a candidate who had told us nothing still matched
 * everything. Sir asked for a "show me jobs above 80%" threshold — which is
 * meaningless on a scorer that cannot produce a low number.
 */
class JobMatchingTest extends TestCase
{
    use RefreshDatabase;

    private function candidate(array $profile = []): User
    {
        $candidate = User::where('email', 'candidate@luckyboss.test')->firstOrFail();

        $candidate->candidateProfile->forceFill(array_merge([
            'current_title' => null,
            'current_location' => null,
            'country_code' => null,
            'years_experience' => null,
            'expected_salary' => null,
            'skills' => null,
            'professional_summary' => null,
            'resume_data' => null,
        ], $profile))->save();

        return $candidate->fresh('candidateProfile');
    }

    private function warehouseProfile(): array
    {
        return [
            'current_title' => 'Warehouse Coordinator',
            'current_location' => 'Singapore',
            'country_code' => 'SG',
            'years_experience' => 4,
            'expected_salary' => 3000,
            'skills' => ['Forklift', 'Inventory', 'Warehouse', 'Logistics', 'Picking', 'Packing'],
        ];
    }

    public function test_an_empty_profile_is_not_scored_at_all(): void
    {
        $this->seed();
        $candidate = $this->candidate();

        $matcher = app(JobMatchService::class);

        $this->assertFalse($matcher->readiness($candidate)['ready']);
        $this->assertNull(
            $matcher->score(Job::where('status', 'published')->firstOrFail(), $candidate),
            'A candidate who has told us nothing must not receive a match percentage.'
        );
        $this->assertCount(
            0,
            $matcher->rank(Job::where('status', 'published')->get(), $candidate),
            'An unmatchable candidate gets an empty list, not every job on the platform.'
        );
    }

    public function test_the_dashboard_asks_for_a_resume_instead_of_listing_unmatched_jobs(): void
    {
        $this->seed();
        $candidate = $this->candidate();

        $this->actingAs($candidate)
            ->get(route('seeker.dashboard', ['tab' => 'matching']))
            ->assertOk()
            ->assertSee('Upload your resume to see your matches')
            // The heading that used to sit above an unranked list of everything.
            ->assertDontSee('Curated Job Openings')
            // And no invented career for someone who has filled nothing in.
            ->assertDontSee('4 Years Experience');
    }

    public function test_a_real_profile_produces_a_spread_of_scores_with_no_floor(): void
    {
        $this->seed();
        $candidate = $this->candidate($this->warehouseProfile());

        $ranked = app(JobMatchService::class)
            ->rank(Job::where('status', 'published')->get(), $candidate);

        $this->assertTrue($ranked->isNotEmpty());

        $scores = $ranked->pluck('match_score');
        $this->assertLessThan(45, $scores->min(), 'Scoring must be able to say a job is a poor fit.');
        $this->assertSame(
            $scores->sortDesc()->values()->all(),
            $scores->values()->all(),
            'rank() returns best first.'
        );
    }

    public function test_a_high_score_cannot_be_claimed_on_a_thin_profile(): void
    {
        $this->seed();

        // Location and years only. Note a job title would *not* be thin: the
        // words in "Warehouse Coordinator" are themselves skill terms, so
        // stating one lets the skills dimension be assessed too.
        $thin = $this->candidate([
            'current_location' => 'Singapore',
            'country_code' => 'SG',
            'years_experience' => 4,
        ]);

        $ranked = app(JobMatchService::class)
            ->rank(Job::where('status', 'published')->get(), $thin);

        $this->assertTrue($ranked->isNotEmpty());

        foreach ($ranked as $job) {
            $this->assertSame(40, $job->match_confidence, 'Only two of five dimensions were comparable.');
            $this->assertLessThanOrEqual(
                70,
                $job->match_score,
                'A perfect result on the little we checked must not be published as a near-perfect match.'
            );
        }
    }

    public function test_the_admin_threshold_filters_the_list(): void
    {
        $this->seed();
        $candidate = $this->candidate($this->warehouseProfile());

        $jobs = Job::where('status', 'published')->get();
        $matcher = app(JobMatchService::class);

        $all = $matcher->rank($jobs, $candidate)->count();
        $strict = $matcher->rank($jobs, $candidate, 90)->count();

        $this->assertLessThan($all, $strict);
        $this->assertTrue(
            $matcher->rank($jobs, $candidate, 90)->every(fn (Job $j) => $j->match_score >= 90)
        );
    }

    public function test_apply_all_only_sends_applications_above_the_threshold(): void
    {
        $this->seed();
        $candidate = $this->candidate($this->warehouseProfile());
        JobApplication::where('candidate_id', $candidate->id)->delete();

        AdminRecord::updateOrCreate(
            ['module' => 'matching', 'slug' => 'job-matching'],
            ['name' => 'Job Matching', 'payload' => ['minimum_match_score' => 60, 'bulk_apply_enabled' => true, 'bulk_apply_limit' => 25]]
        );

        $expected = app(JobMatchService::class)
            ->rank(Job::where('status', 'published')->get(), $candidate, 60);

        $this->actingAs($candidate)->post(route('seeker.jobs.apply-all'))->assertRedirect();

        $created = JobApplication::where('candidate_id', $candidate->id)->get();

        $this->assertCount($expected->count(), $created);
        $this->assertGreaterThan(0, $created->count());

        foreach ($created as $application) {
            $this->assertGreaterThanOrEqual(
                60,
                (int) $application->match_score,
                'Apply All must never send an application below the admin threshold.'
            );
        }
    }

    public function test_apply_all_does_not_duplicate_on_a_second_tap(): void
    {
        $this->seed();
        $candidate = $this->candidate($this->warehouseProfile());
        JobApplication::where('candidate_id', $candidate->id)->delete();

        $this->actingAs($candidate)->post(route('seeker.jobs.apply-all'))->assertRedirect();
        $first = JobApplication::where('candidate_id', $candidate->id)->count();

        $this->actingAs($candidate)->post(route('seeker.jobs.apply-all'))->assertRedirect();

        $this->assertSame($first, JobApplication::where('candidate_id', $candidate->id)->count());
    }

    public function test_apply_all_refuses_when_there_is_nothing_to_match_on(): void
    {
        $this->seed();
        $candidate = $this->candidate();
        JobApplication::where('candidate_id', $candidate->id)->delete();

        $this->actingAs($candidate)->post(route('seeker.jobs.apply-all'))->assertRedirect();

        $this->assertSame(
            0,
            JobApplication::where('candidate_id', $candidate->id)->count(),
            'An empty profile must not be blasted at every employer on the platform.'
        );
    }

    public function test_apply_all_honours_the_admin_off_switch(): void
    {
        $this->seed();
        $candidate = $this->candidate($this->warehouseProfile());
        JobApplication::where('candidate_id', $candidate->id)->delete();

        AdminRecord::updateOrCreate(
            ['module' => 'matching', 'slug' => 'job-matching'],
            ['name' => 'Job Matching', 'payload' => ['bulk_apply_enabled' => false]]
        );

        $this->actingAs($candidate)->post(route('seeker.jobs.apply-all'))->assertRedirect();

        $this->assertSame(0, JobApplication::where('candidate_id', $candidate->id)->count());
    }

    public function test_apply_all_is_capped_by_the_admin_limit(): void
    {
        $this->seed();
        $candidate = $this->candidate($this->warehouseProfile());
        JobApplication::where('candidate_id', $candidate->id)->delete();

        AdminRecord::updateOrCreate(
            ['module' => 'matching', 'slug' => 'job-matching'],
            ['name' => 'Job Matching', 'payload' => ['minimum_match_score' => 0, 'bulk_apply_enabled' => true, 'bulk_apply_limit' => 2]]
        );

        $this->actingAs($candidate)->post(route('seeker.jobs.apply-all'))->assertRedirect();

        $this->assertSame(2, JobApplication::where('candidate_id', $candidate->id)->count());
    }

    public function test_the_api_dashboard_reports_readiness_rather_than_faking_recommendations(): void
    {
        $this->seed();
        $candidate = $this->candidate();

        $response = $this->actingAs($candidate, 'sanctum')
            ->getJson('/api/v1/job-seeker/dashboard')
            ->assertOk();

        $response->assertJsonPath('match_readiness.ready', false);
        $this->assertSame([], $response->json('recommended_jobs'));
        $this->assertIsInt($response->json('minimum_match_score'));
    }

    public function test_applying_to_one_job_stores_a_real_score_or_none(): void
    {
        $this->seed();
        $candidate = $this->candidate();
        JobApplication::where('candidate_id', $candidate->id)->delete();

        $job = Job::where('status', 'published')->firstOrFail();
        $this->actingAs($candidate)->post(route('seeker.jobs.apply', $job))->assertRedirect();

        $application = JobApplication::where('candidate_id', $candidate->id)->firstOrFail();

        // Previously this fell back to 88, and the employer was told so.
        $this->assertNull($application->match_score);
    }
}
