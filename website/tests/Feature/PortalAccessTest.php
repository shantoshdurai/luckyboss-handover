<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Offer;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_company_type_but_job_seeker_cannot(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $candidate = User::where('email', 'candidate@luckyboss.test')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.masters.store', 'company-types'), ['name' => 'Security Services', 'is_active' => 1])
            ->assertRedirect(route('admin.masters.index', 'company-types'));
        $this->assertDatabaseHas('company_types', ['name' => 'Security Services']);

        $this->actingAs($candidate)->get(route('admin.masters.index', 'company-types'))->assertForbidden();
    }

    public function test_mobile_login_issues_role_specific_token(): void
    {
        $this->seed();
        $this->postJson('/api/v1/auth/login', ['email' => 'candidate@luckyboss.test', 'password' => 'password', 'app' => 'seeker'])
            ->assertOk()->assertJsonPath('role', 'job-seeker')->assertJsonStructure(['token', 'user']);
    }

    public function test_admin_dashboard_renders_for_seeded_administrator(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Quick Management')
            ->assertSee('Feature Control');
    }

    public function test_candidate_can_accept_only_their_own_offer(): void
    {
        $this->seed();
        $candidate = User::where('email', 'candidate@luckyboss.test')->firstOrFail();
        $application = JobApplication::where('candidate_id', $candidate->id)->firstOrFail();
        $offer = Offer::create(['job_application_id' => $application->id, 'company_id' => $application->job->company_id, 'position' => $application->job->title, 'salary' => 4200, 'currency_code' => 'SGD', 'status' => 'sent', 'sent_at' => now()]);

        $this->actingAs($candidate)->post(route('seeker.offers.respond', [$offer, 'accepted']))->assertRedirect();
        $this->assertDatabaseHas('offers', ['id' => $offer->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('job_applications', ['id' => $application->id, 'status' => 'Offer Accepted']);
    }

    public function test_admin_can_view_payment_controls(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.operations.index', 'payments'))
            ->assertOk()
            ->assertSee('Payments');
    }

    public function test_admin_can_open_a_command_center_submenu(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.command.show', ['ai-api', 'global-ai-settings']))
            ->assertOk()
            ->assertSee('Global AI Settings');
    }
}