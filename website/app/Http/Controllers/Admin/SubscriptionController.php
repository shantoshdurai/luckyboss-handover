<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Package;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'active-subscriptions';
        $query = Subscription::with(['company', 'package'])->latest();
        match ($view) {
            'active-subscriptions' => $query->where('status', 'active')->whereDate('expires_at', '>=', today()),
            'expired-subscriptions' => $query->where(fn ($builder) => $builder->where('status', 'expired')->orWhereDate('expires_at', '<', today())),
            'expiring-soon' => $query->where('status', 'active')->whereBetween('expires_at', [today(), today()->addDays(30)]),
            'free-trials' => $query->where('status', 'trial'),
            'manual-assignments' => $query->where('status', 'manual'),
            default => null,
        };
        return view('admin.subscriptions.index', ['subscriptions' => $query->paginate(20)->withQueryString(), 'view' => $view, 'companies' => Company::orderBy('name')->get(), 'packages' => Package::where('is_active', true)->orderBy('name')->get(), 'filters' => $request->only(['view'])]);
    }

    public function assign(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['company_id' => ['required', 'exists:companies,id'], 'package_id' => ['required', 'exists:packages,id'], 'starts_at' => ['required', 'date'], 'expires_at' => ['required', 'date', 'after_or_equal:starts_at'], 'amount' => ['required', 'numeric', 'min:0'], 'currency_code' => ['required', 'string', 'size:3']]);
        $package = Package::findOrFail($data['package_id']);
        Subscription::create($data + ['status' => 'manual', 'entitlements' => $package->entitlements]);
        return back()->with('success', 'Subscription assigned manually.');
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['status' => ['required', 'in:pending,active,expired,trial,manual,cancelled'], 'expires_at' => ['nullable', 'date'], 'amount' => ['required', 'numeric', 'min:0']]);
        $subscription->update($data);
        return back()->with('success', 'Subscription updated.');
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $this->ensureAdmin(); $subscription->delete(); return back()->with('success', 'Subscription deleted.');
    }
}
