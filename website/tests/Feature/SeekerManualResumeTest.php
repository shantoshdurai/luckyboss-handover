<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeekerManualResumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_seeker_can_save_manual_resume_sections(): void
    {
        $this->seed();
        $candidate = User::where('email', 'candidate@luckyboss.test')->firstOrFail();
        $this->actingAs($candidate)->get(route('seeker.profile.edit'))->assertOk()->assertSee('Manual Resume Profile');
        $response = $this->actingAs($candidate)->put(route('seeker.profile.update'), [
            'name' => 'Maya Updated', 'email' => $candidate->email, 'phone' => $candidate->phone, 'current_title' => 'Senior Coordinator', 'professional_summary' => 'Experienced operator.', 'date_of_birth' => '1995-01-01', 'gender' => 'Female', 'current_location' => 'Singapore', 'years_experience' => 5, 'notice_period' => '30 days', 'experience' => json_encode([['title' => 'Coordinator', 'company' => 'Acme', 'achievements' => 'Improved workflow']]), 'education' => json_encode([['degree' => 'Diploma', 'institution' => 'College']]), 'skills' => json_encode([['skill' => 'Excel', 'proficiency' => 'Advanced', 'last_used_year' => 2026]]), 'international_jobs' => json_encode([['passport_valid' => true, 'ready_abroad' => true]]), 'declaration' => json_encode([['place' => 'Singapore', 'date' => '2026-08-20']]),
        ]);
        $response->assertRedirect();
        $profile = CandidateProfile::where('user_id', $candidate->id)->firstOrFail();
        $this->assertSame('Senior Coordinator', $profile->current_title);
        $this->assertSame('Coordinator', data_get($profile->resume_data, 'experience.0.title'));
        $this->assertSame('Advanced', data_get($profile->resume_data, 'skills.0.proficiency'));
    }
}
