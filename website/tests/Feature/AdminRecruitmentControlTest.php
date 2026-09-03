<?php

namespace Tests\Feature;

use App\Models\ApplicationStatusHistory;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRecruitmentControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_recruitment_stages_render_in_command_center_and_ats(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $views = ['all-applications', 'new-applications', 'shortlisted', 'contacted', 'interview-scheduled', 'interviewed', 'assessment', 'selected', 'offer-prepared', 'offer-sent', 'offer-accepted', 'joined', 'rejected', 'archived-candidates'];
        foreach ($views as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['recruitment', $view]))->assertOk()->assertSee('Live controls');
            $this->actingAs($admin)->get(route('admin.recruitment.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_progress_application_schedule_interview_and_create_offer(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $application = JobApplication::firstOrFail();

        $this->actingAs($admin)->post(route('admin.recruitment.status', $application), ['status' => 'Shortlisted', 'remark' => 'Strong profile'])->assertRedirect();
        $this->assertDatabaseHas('job_applications', ['id' => $application->id, 'status' => 'Shortlisted']);
        $this->assertDatabaseHas('application_status_histories', ['job_application_id' => $application->id, 'to_status' => 'Shortlisted']);
        $this->actingAs($admin)->post(route('admin.recruitment.interview', $application), ['scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'), 'mode' => 'Zoom', 'duration_minutes' => 45])->assertRedirect();
        $this->assertDatabaseHas('interviews', ['job_application_id' => $application->id, 'status' => 'scheduled']);
        $this->actingAs($admin)->post(route('admin.recruitment.offer', $application), ['salary' => 5000, 'currency_code' => 'SGD'])->assertRedirect();
        $this->assertDatabaseHas('offers', ['job_application_id' => $application->id, 'status' => 'sent']);
    }

    public function test_admin_can_delete_application(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $application = JobApplication::firstOrFail();
        $this->actingAs($admin)->delete(route('admin.recruitment.destroy', $application))->assertRedirect();
        $this->assertDatabaseMissing('job_applications', ['id' => $application->id]);
    }
}
