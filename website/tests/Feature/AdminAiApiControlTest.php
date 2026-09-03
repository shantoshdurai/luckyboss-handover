<?php

namespace Tests\Feature;

use App\Models\ApiIntegration;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAiApiControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_ai_api_modules_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $views = ['ai-dashboard', 'global-ai-settings', 'platform-ai', 'employer-byoai', 'resume-parser', 'embedding-api', 'vector-database', 'voice-api', 'whatsapp-api', 'email-api', 'push-notifications', 'google-calendar', 'payment-api', 'api-usage', 'api-errors', 'cost-monitoring'];
        foreach ($views as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['ai-api', $view]))->assertOk()->assertSee('Live controls');
            $this->actingAs($admin)->get(route('admin.ai-api.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_toggle_flags_integrations_and_clear_errors(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $flag = FeatureFlag::firstOrFail();
        $integration = ApiIntegration::firstOrFail();
        $this->actingAs($admin)->put(route('admin.ai-api.flags.update', $flag), ['is_enabled' => 1])->assertRedirect();
        $this->assertDatabaseHas('feature_flags', ['id' => $flag->id, 'is_enabled' => 1]);
        $this->actingAs($admin)->put(route('admin.ai-api.integrations.update', $integration), ['is_enabled' => 1])->assertRedirect();
        $this->assertDatabaseHas('api_integrations', ['id' => $integration->id, 'is_enabled' => 1]);
        $integration->update(['last_error' => 'Temporary failure']);
        $this->actingAs($admin)->delete(route('admin.ai-api.integrations.error.clear', $integration))->assertRedirect();
        $this->assertDatabaseHas('api_integrations', ['id' => $integration->id, 'last_error' => null]);
    }
}
