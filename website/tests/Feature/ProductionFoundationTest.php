<?php

namespace Tests\Feature;

use App\Models\ApiIntegration;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\AIProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ProductionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_finance_role_has_only_payment_permission(): void
    {
        $this->seed();
        $role = Role::where('slug', 'finance-admin')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('payments.manage'));
        $this->assertFalse($user->hasPermission('api.manage'));
    }

    public function test_admin_integration_secrets_are_encrypted_at_rest(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.operations.store', 'integrations'), [
            'name' => 'Stripe Test', 'provider' => 'Stripe', 'environment' => 'sandbox', 'api_key' => 'sk_test_secret', 'webhook_secret' => 'whsec_test_secret', 'is_enabled' => 1,
        ])->assertRedirect();

        $integration = ApiIntegration::where('key', 'stripe_test')->firstOrFail();
        $this->assertNotSame('sk_test_secret', $integration->encrypted_secret);
        $this->assertSame('cret', $integration->webhook_secret_hint);
    }

    public function test_manual_sandbox_webhook_marks_payment_paid(): void
    {
        $this->seed();
        $payment = Payment::where('reference', 'LB-DEMO-001')->firstOrFail();
        $payment->update(['status' => 'pending']);

        $this->withHeader('X-LuckyBoss-Test', 'sandbox')
            ->postJson('/api/v1/webhooks/payments/manual', ['reference' => 'LB-DEMO-001', 'status' => 'paid'])
            ->assertOk()->assertJsonPath('received', true);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
    }

    public function test_ai_provider_requires_configured_secret_before_becoming_available(): void
    {
        $this->seed();

        ApiIntegration::updateOrCreate(['key' => 'platform_openai'], [
            'name' => 'OpenAI GPT',
            'provider' => 'OpenAI',
            'environment' => 'sandbox',
            'is_enabled' => true,
            'encrypted_secret' => null,
        ]);

        $manager = new AIProviderManager();
        $this->assertFalse($manager->available('platform_openai'));
        $this->assertSame('rule-based', $manager->fallbackMatch()['provider']);

        ApiIntegration::where('key', 'platform_openai')->update([
            'encrypted_secret' => Crypt::encryptString('sk-test-valid-key'),
        ]);

        $this->assertTrue($manager->available('platform_openai'));
    }
}