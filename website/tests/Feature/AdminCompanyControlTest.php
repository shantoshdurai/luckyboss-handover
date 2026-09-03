<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmployerNote;
use App\Models\EmployerDocument;
use App\Models\CandidateSkill;
use App\Models\AdminRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompanyControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_and_change_company_status(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $company = Company::create(['name' => 'Acme Logistics', 'email' => 'ops@acme.test', 'country_code' => 'SG', 'status' => 'pending']);
        Company::create(['name' => 'Other Company', 'country_code' => 'MY', 'status' => 'pending']);

        $this->actingAs($admin)->get(route('admin.companies.index', ['search' => 'Acme']))
            ->assertOk()
            ->assertSee('Acme Logistics')
            ->assertDontSee('Other Company');
        $this->actingAs($admin)->post(route('admin.companies.status', [$company, 'verified']))->assertRedirect();
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'status' => 'verified']);
    }

    public function test_admin_can_create_update_and_delete_employer_note(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $company = Company::firstOrFail();

        $this->actingAs($admin)->post(route('admin.employer-notes.store'), ['company_id' => $company->id, 'note' => 'Follow up on documents.'])->assertRedirect();
        $note = EmployerNote::firstOrFail();
        $this->assertSame($admin->id, $note->user_id);
        $this->actingAs($admin)->put(route('admin.employer-notes.update', $note), ['company_id' => $company->id, 'note' => 'Documents verified.'])->assertRedirect();
        $this->assertDatabaseHas('employer_notes', ['id' => $note->id, 'note' => 'Documents verified.']);
        $this->actingAs($admin)->delete(route('admin.employer-notes.destroy', $note))->assertRedirect();
        $this->assertDatabaseMissing('employer_notes', ['id' => $note->id]);
    }

    public function test_non_super_admin_cannot_access_company_controls(): void
    {
        $this->seed();
        $candidate = User::where('email', 'candidate@luckyboss.test')->firstOrFail();
        $this->actingAs($candidate)->get(route('admin.companies.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.employer-notes.index'))->assertForbidden();
    }

    public function test_employer_command_modules_use_real_control_summaries(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.command.show', ['employers', 'all-companies']))
            ->assertOk()
            ->assertSee('Live controls')
            ->assertSee('Manage All Companies')
            ->assertDontSee('This secured admin workspace is ready for the selected command-center module.');
        $this->actingAs($admin)->get(route('admin.command.show', ['employers', 'employer-notes']))
            ->assertOk()
            ->assertSee('Manage Employer Notes');
    }

    public function test_employer_master_links_are_not_404(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.command.show', ['employers', 'company-types']))
            ->assertOk()
            ->assertSee('Manage Company Types');
        $this->actingAs($admin)->get(route('admin.command.show', ['employers', 'company-grades']))
            ->assertOk()
            ->assertSee('Manage Company Grades');
    }

    public function test_admin_can_manage_employer_access_and_documents(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $company = Company::firstOrFail();
        $employer = User::whereHas('roles', fn ($query) => $query->where('slug', 'employer'))->firstOrFail();

        $this->actingAs($admin)->post(route('admin.employer-users.toggle', [$employer, $company]))->assertRedirect();
        $this->assertDatabaseHas('company_users', ['company_id' => $company->id, 'user_id' => $employer->id, 'is_active' => 0]);
        $this->actingAs($admin)->post(route('admin.employer-documents.store'), ['company_id' => $company->id, 'name' => 'Business License', 'status' => 'pending'])->assertRedirect();
        $document = EmployerDocument::firstOrFail();
        $this->actingAs($admin)->put(route('admin.employer-documents.update', $document), ['name' => 'Business License', 'status' => 'approved'])->assertRedirect();
        $this->assertDatabaseHas('employer_documents', ['id' => $document->id, 'status' => 'approved']);
        $this->actingAs($admin)->get(route('admin.employer-activity.index'))->assertOk()->assertSee('Employer Activity');
    }

    public function test_admin_sidebar_uses_saved_branding_logo(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        AdminRecord::updateOrCreate(['module' => 'branding', 'slug' => 'website-branding'], ['name' => 'Website Branding', 'payload' => ['logo_url' => 'https://cdn.example.test/custom-admin-logo.png'], 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('https://cdn.example.test/custom-admin-logo.png');
    }

    public function test_all_job_seeker_command_modules_render_and_candidate_crud_works(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $candidate = User::whereHas('roles', fn ($query) => $query->where('slug', 'job-seeker'))->firstOrFail();
        $views = ['all-job-seekers', 'new-registrations', 'verified-candidates', 'incomplete-profiles', 'complete-profiles', 'blocked-candidates', 'candidate-resumes', 'candidate-skills', 'candidate-applications', 'candidate-purchases', 'candidate-login-history', 'candidate-notes'];
        foreach ($views as $view) {
            $response = $this->actingAs($admin)->get(route('admin.command.show', ['job-seekers', $view]))->assertOk();
            $response->assertSee($view === 'candidate-login-history' ? 'Read-only audit' : 'Live controls');
            $this->actingAs($admin)->get(route('admin.candidates.index', ['view' => $view]))->assertOk();
        }
        $this->actingAs($admin)->post(route('admin.candidate-skills.store'), ['candidate_id' => $candidate->id, 'name' => 'Laravel', 'level' => 'Advanced'])->assertRedirect();
        $this->assertDatabaseHas('candidate_skills', ['candidate_id' => $candidate->id, 'name' => 'Laravel']);
        $this->actingAs($admin)->post(route('admin.candidates.toggle', $candidate))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $candidate->id, 'is_active' => 0]);
    }
}
