<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class WebhookController extends Controller
{
    public function payment(Request $request, string $gateway)
    {
        abort_unless(in_array($gateway, ['stripe', 'razorpay', 'manual'], true), 404);
        $payload = $request->getContent();

        if ($gateway === 'manual') {
            abort_unless(app()->environment(['local', 'testing']) && $request->header('X-LuckyBoss-Test') === 'sandbox', 403);
        } else {
            $integration = ApiIntegration::query()
                ->whereRaw('LOWER(provider) = ?', [$gateway])
                ->where('is_enabled', true)
                ->firstOrFail();
            abort_unless($integration->encrypted_webhook_secret, 403, 'Webhook secret is not configured.');
            $expected = hash_hmac('sha256', $payload, Crypt::decryptString($integration->encrypted_webhook_secret));
            abort_unless(hash_equals($expected, (string) $request->header('X-LuckyBoss-Signature')), 403, 'Invalid webhook signature.');
        }

        $data = $request->validate(['reference' => 'required|string', 'status' => 'required|in:paid,failed,pending']);
        $payment = Payment::where('reference', $data['reference'])->firstOrFail();
        if ($data['status'] === 'paid') app(PaymentService::class)->markPaid($payment); else $payment->update(['status' => $data['status']]);

        return response()->json(['received' => true]);
    }
}