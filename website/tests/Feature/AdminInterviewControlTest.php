<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInterviewControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_interview_modules_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        foreach (['all-interviews', 'today-interviews', 'upcoming-interviews', 'completed-interviews', 'cancelled-interviews', 'interview-modes', 'interview-feedback', 'calendar-connections'] as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['interviews', $view]))->assertOk()->assertSee('Live controls');
            $this->actingAs($admin)->get(route('admin.interviews.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_update_interview_and_create_mode_and_connection(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $application = \App\Models\JobApplication::firstOrFail();
        $interview = Interview::create(['job_application_id' => $application->id, 'company_id' => $application->job->company_id, 'interviewer_id' => $admin->id, 'mode' => 'Zoom', 'scheduled_at' => now()->addDay(), 'duration_minutes' => 45, 'time_zone' => 'Asia/Singapore', 'status' => 'scheduled']);
        $this->actingAs($admin)->put(route('admin.interviews.update', $interview), ['status' => 'completed', 'mode' => 'Zoom', 'scheduled_at' => $interview->scheduled_at->format('Y-m-d H:i:s'), 'notes' => 'Strong interview'])->assertRedirect();
        $this->assertDatabaseHas('interviews', ['id' => $interview->id, 'status' => 'completed', 'notes' => 'Strong interview']);
        $this->actingAs($admin)->post(route('admin.interviews.modes.store'), ['name' => 'Phone Screen', 'description' => 'Initial call'])->assertRedirect();
        $this->assertDatabaseHas('admin_records', ['module' => 'interview-modes', 'name' => 'Phone Screen']);
        $this->actingAs($admin)->post(route('admin.interviews.connections.store'), ['name' => 'Google Calendar', 'description' => 'Primary calendar'])->assertRedirect();
        $this->assertDatabaseHas('admin_records', ['module' => 'calendar-connections', 'name' => 'Google Calendar']);
    }
}
