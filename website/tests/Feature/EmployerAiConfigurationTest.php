<?php

namespace Tests\Feature;

use App\Models\EmployerPortalRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerAiConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_save_masked_byoai_configuration(): void
    {
        $this->seed();
        $employer = User::where('email', 'employer@luckyboss.test')->firstOrFail();
        $secret = 'sk-test-company-secret';

        $this->actingAs($employer)->put(route('employer.ai-configuration.update'), [
            'mode' => 'automatic',
            'provider' => 'OpenAI',
            'model' => 'default-recommended',
            'api_key' => $secret,
            'company_ai_enabled' => 1,
        ])->assertRedirect();

        $record = EmployerPortalRecord::where('company_id', $employer->companies()->firstOrFail()->id)->where('section', 'ai-configuration')->firstOrFail();
        $this->assertArrayHasKey('encrypted_api_key', $record->payload);
        $this->assertStringNotContainsString($secret, $record->payload['encrypted_api_key']);
        $this->actingAs($employer)->get(route('employer.portal', 'ai-tools'))->assertOk()->assertSee('AI Configuration')->assertDontSee($secret);

        $this->actingAs($employer)->delete(route('employer.ai-configuration.remove'))->assertRedirect();
        $this->assertArrayNotHasKey('encrypted_api_key', $record->fresh()->payload);
    }
}
