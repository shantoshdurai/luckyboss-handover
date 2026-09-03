<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_payment_modules_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        foreach (['all-transactions', 'employer-payments', 'job-seeker-payments', 'subscription-payments', 'paid-job-applications', 'add-on-payments', 'failed-payments', 'refunds', 'payment-gateways', 'payment-webhooks', 'payment-logs', 'invoices'] as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['payments', $view]))->assertOk()->assertSee('Live controls');
            $this->actingAs($admin)->get(route('admin.payments.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_update_and_refund_payment_and_invoice(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $payment = Payment::firstOrFail();
        $this->actingAs($admin)->put(route('admin.payments.update', $payment), ['status' => 'refunded'])->assertRedirect();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
        $invoice = Invoice::firstOrFail();
        $this->actingAs($admin)->put(route('admin.invoices.update', $invoice), ['status' => 'paid'])->assertRedirect();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }
}
