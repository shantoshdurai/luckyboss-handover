<?php

namespace Tests\Feature;

use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommunicationControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_communication_modules_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        foreach (['email-templates', 'whatsapp-templates', 'interview-templates', 'offer-templates', 'rejection-templates', 'joining-templates', 'communication-history', 'scheduled-messages'] as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['communication', $view]))->assertOk()->assertSee('Live controls');
            $this->actingAs($admin)->get(route('admin.communication.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_create_update_and_delete_template_and_log(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.communication.templates.store'), ['type' => 'email', 'name' => 'Welcome Email', 'subject' => 'Welcome', 'body' => 'Hello {name}', 'is_active' => 1])->assertRedirect();
        $template = CommunicationTemplate::where('name', 'Welcome Email')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.communication.templates.update', $template), ['type' => 'email', 'name' => 'Welcome Updated', 'subject' => 'Welcome', 'body' => 'Hello {name}', 'is_active' => 1])->assertRedirect();
        $this->assertDatabaseHas('communication_templates', ['id' => $template->id, 'name' => 'Welcome Updated']);
        $log = CommunicationLog::create(['channel' => 'email', 'status' => 'queued', 'subject' => 'Test', 'body' => 'Body']);
        $this->actingAs($admin)->delete(route('admin.communication.logs.destroy', $log))->assertRedirect();
        $this->assertDatabaseMissing('communication_logs', ['id' => $log->id]);
        $this->actingAs($admin)->delete(route('admin.communication.templates.destroy', $template))->assertRedirect();
    }
}
