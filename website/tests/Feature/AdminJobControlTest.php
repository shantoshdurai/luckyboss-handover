<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminJobControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_job_command_modules_and_admin_views_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $views = ['all-jobs', 'pending-approval', 'approved-jobs', 'active-jobs', 'expired-jobs', 'rejected-jobs', 'featured-jobs', 'urgent-jobs', 'sponsored-jobs', 'apply-soon-jobs', 'paid-apply-jobs', 'external-jobs', 'archived-jobs'];
        foreach ($views as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['jobs', $view]))->assertOk()->assertSee('Live controls');
            $this->actingAs($admin)->get(route('admin.jobs.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_publish_flag_archive_and_delete_job(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $job = Job::firstOrFail();

        $this->actingAs($admin)->post(route('admin.jobs.status', [$job, 'published']))->assertRedirect();
        $this->assertDatabaseHas('jobs', ['id' => $job->id, 'status' => 'published']);
        $this->actingAs($admin)->post(route('admin.jobs.flag', [$job, 'is_sponsored']))->assertRedirect();
        $this->assertDatabaseHas('jobs', ['id' => $job->id, 'is_sponsored' => 1]);
        $this->actingAs($admin)->post(route('admin.jobs.status', [$job, 'archived']))->assertRedirect();
        $this->assertDatabaseHas('jobs', ['id' => $job->id, 'status' => 'archived']);
        $this->actingAs($admin)->delete(route('admin.jobs.destroy', $job))->assertRedirect();
        $this->assertDatabaseMissing('jobs', ['id' => $job->id]);
    }
}
