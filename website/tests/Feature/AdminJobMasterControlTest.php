<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminJobMasterControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_job_master_modules_render_without_404(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $views = ['industries', 'job-categories', 'job-subcategories', 'job-roles', 'skills', 'experience-levels', 'education-levels', 'certifications', 'job-types', 'work-modes', 'shifts', 'salary-types', 'notice-period', 'employment-status', 'visa-work-permit-types'];
        foreach ($views as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['job-masters', $view]))->assertOk();
        }
    }

    public function test_generic_job_master_records_support_crud(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $module = 'job-master-skills';
        $this->actingAs($admin)->post(route('admin.records.store', $module), ['name' => 'Forklift Operation', 'description' => 'Warehouse skill', 'is_active' => 1])->assertRedirect();
        $record = AdminRecord::where('module', $module)->where('name', 'Forklift Operation')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.records.update', [$module, $record]), ['name' => 'Advanced Forklift Operation', 'description' => 'Updated skill', 'is_active' => 1])->assertRedirect();
        $this->assertDatabaseHas('admin_records', ['id' => $record->id, 'name' => 'Advanced Forklift Operation']);
        $this->actingAs($admin)->delete(route('admin.records.destroy', [$module, $record]))->assertRedirect();
        $this->assertDatabaseMissing('admin_records', ['id' => $record->id]);
    }

    public function test_categories_support_icon_and_explicit_sort_order(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.masters.store', 'job-categories'), ['name' => 'First Category', 'description' => 'Primary category', 'icon' => 'star', 'sort_order' => 1, 'is_active' => 1])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.masters.store', 'job-categories'), ['name' => 'Second Category', 'description' => 'Secondary category', 'icon' => 'briefcase', 'sort_order' => 2, 'is_active' => 1])->assertRedirect();
        $this->assertDatabaseHas('job_categories', ['name' => 'First Category', 'sort_order' => 1]);
        $content = $this->actingAs($admin)->get(route('admin.masters.index', 'job-categories'))->assertOk()->getContent();
        $this->assertLessThan(strpos($content, 'Second Category'), strpos($content, 'First Category'));
    }
}
