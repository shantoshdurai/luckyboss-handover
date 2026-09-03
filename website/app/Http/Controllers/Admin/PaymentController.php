<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'all-transactions';
        $payments = Payment::with(['company', 'user'])->latest();
        match ($view) {
            'employer-payments' => $payments->whereNotNull('company_id'),
            'job-seeker-payments' => $payments->whereNotNull('user_id'),
            'subscription-payments' => $payments->whereNotNull('subscription_id')->orWhere('purpose', 'subscription'),
            'paid-job-applications' => $payments->where(fn ($builder) => $builder->whereNotNull('job_id')->orWhereIn('purpose', ['paid_job_application', 'paid-apply'])),
            'add-on-payments' => $payments->whereIn('purpose', ['add-on', 'addon']),
            'failed-payments' => $payments->where('status', 'failed'),
            'refunds' => $payments->where('status', 'refunded'),
            'payment-webhooks' => $payments->whereNotNull('gateway_payload'),
            'payment-logs' => null,
            default => null,
        };
        return view('admin.payments.index', ['view' => $view, 'payments' => $payments->paginate(20)->withQueryString(), 'gateways' => ApiIntegration::where(fn ($query) => $query->whereIn('key', ['payment_gateway', 'stripe', 'razorpay'])->orWhere('provider', 'like', '%pay%'))->get(), 'invoices' => Invoice::latest()->paginate(20)->withQueryString(), 'filters' => $request->only('view')]);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['status' => ['required', 'in:pending,paid,failed,refunded']]);
        $payment->update($data + ['paid_at' => $data['status'] === 'paid' ? ($payment->paid_at ?: now()) : $payment->paid_at]);
        return back()->with('success', 'Payment updated.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->ensureAdmin(); $payment->delete(); return back()->with('success', 'Payment deleted.');
    }

    public function updateInvoice(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->ensureAdmin(); $invoice->update($request->validate(['status' => ['required', 'in:draft,issued,paid,void,refunded']])); return back()->with('success', 'Invoice updated.');
    }

    public function destroyInvoice(Invoice $invoice): RedirectResponse
    {
        $this->ensureAdmin(); $invoice->delete(); return back()->with('success', 'Invoice deleted.');
    }
}
