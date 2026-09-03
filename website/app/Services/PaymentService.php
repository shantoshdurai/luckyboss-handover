<?php
namespace App\Services;
use App\Models\Payment;
use Illuminate\Support\Str;
class PaymentService { public function createManual(array $attributes): Payment { return Payment::create($attributes+['reference'=>'LB-'.strtoupper(Str::random(10)),'gateway'=>'manual','status'=>'pending']); } public function markPaid(Payment $payment): Payment { $payment->update(['status'=>'paid','paid_at'=>now()]); return $payment->refresh(); } }