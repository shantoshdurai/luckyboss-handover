<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmployerPortalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerPortalCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_employer_can_open_menu_pages_and_manage_company_records(): void
    {
        $this->seed();
        $employer = User::where('email', 'employer@luckyboss.test')->firstOrFail();
        foreach (['candidates', 'recruitment', 'interviews', 'offers', 'candidate-search', 'messages', 'reports', 'team-users', 'subscription', 'billing', 'ai-tools', 'notifications', 'profile', 'settings', 'support'] as $section) {
            $this->actingAs($employer)->get(route('employer.portal', $section))->assertOk();
        }
        $this->actingAs($employer)->post(route('employer.portal.store', 'messages'), ['name' => 'Candidate outreach', 'description' => 'Template', 'payload' => '{"channel":"email"}'])->assertRedirect();
        $record = EmployerPortalRecord::where('section', 'messages')->firstOrFail();
        $this->actingAs($employer)->put(route('employer.portal.update', $record), ['name' => 'Updated outreach', 'description' => 'Updated', 'payload' => '{}', 'is_active' => 1])->assertRedirect();
        $this->assertDatabaseHas('employer_portal_records', ['id' => $record->id, 'name' => 'Updated outreach']);
        $this->actingAs($employer)->delete(route('employer.portal.destroy', $record))->assertRedirect();
    }
}
