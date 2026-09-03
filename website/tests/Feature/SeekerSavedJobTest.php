<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeekerSavedJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_seeker_can_save_and_unsave_a_job(): void
    {
        $this->seed();
        $candidate = User::where('email', 'candidate@luckyboss.test')->firstOrFail();
        $job = Job::firstOrFail();
        $this->actingAs($candidate)->post(route('seeker.jobs.save', $job))->assertRedirect();
        $this->assertDatabaseHas('saved_jobs', ['candidate_id' => $candidate->id, 'job_id' => $job->id]);
        $this->actingAs($candidate)->post(route('seeker.jobs.save', $job))->assertRedirect();
        $this->assertDatabaseMissing('saved_jobs', ['candidate_id' => $candidate->id, 'job_id' => $job->id]);
    }
}
