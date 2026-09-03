<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSupportReportControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_support_and_report_modules_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        foreach (['all-queries', 'new-queries', 'open', 'in-progress', 'resolved', 'closed', 'assigned-agent'] as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['support', $view]))->assertOk();
            $this->actingAs($admin)->get(route('admin.support-center.index', ['view' => $view]))->assertOk();
        }
        foreach (['employer-reports', 'job-seeker-reports', 'job-reports', 'application-reports', 'interview-reports', 'offer-reports', 'hiring-reports', 'subscription-reports', 'payment-reports', 'revenue-reports', 'ai-usage-reports', 'api-cost-reports', 'candidate-source-reports', 'external-data-reports', 'country-reports', 'category-reports'] as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['reports', $view]))->assertOk();
            $this->actingAs($admin)->get(route('admin.reports.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_update_assign_and_delete_support_ticket(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $ticket = SupportTicket::firstOrFail();
        $this->actingAs($admin)->put(route('admin.support-center.update', $ticket), ['status' => 'in-progress', 'priority' => 'high', 'assigned_to' => $admin->id])->assertRedirect();
        $this->assertDatabaseHas('support_tickets', ['id' => $ticket->id, 'status' => 'in-progress', 'assigned_to' => $admin->id]);
        $this->actingAs($admin)->delete(route('admin.support-center.destroy', $ticket))->assertRedirect();
        $this->assertDatabaseMissing('support_tickets', ['id' => $ticket->id]);
    }
}
