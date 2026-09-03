<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_subscription_modules_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        foreach (['packages', 'package-features', 'package-prices', 'active-subscriptions', 'expired-subscriptions', 'expiring-soon', 'free-trials', 'manual-assignments', 'subscription-history', 'usage-credits'] as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['subscriptions', $view]))->assertOk()->assertSee('Live controls');
            $this->actingAs($admin)->get(route('admin.subscriptions.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_assign_update_and_delete_subscription(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $company = Company::firstOrFail();
        $package = Package::firstOrFail();
        $this->actingAs($admin)->post(route('admin.subscriptions.assign'), ['company_id' => $company->id, 'package_id' => $package->id, 'starts_at' => today()->format('Y-m-d'), 'expires_at' => today()->addDays(30)->format('Y-m-d'), 'amount' => 99, 'currency_code' => 'SGD'])->assertRedirect();
        $subscription = Subscription::where('status', 'manual')->latest()->firstOrFail();
        $this->actingAs($admin)->put(route('admin.subscriptions.update', $subscription), ['status' => 'active', 'expires_at' => today()->addDays(60)->format('Y-m-d'), 'amount' => 99])->assertRedirect();
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'active']);
        $this->actingAs($admin)->delete(route('admin.subscriptions.destroy', $subscription))->assertRedirect();
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }
}
