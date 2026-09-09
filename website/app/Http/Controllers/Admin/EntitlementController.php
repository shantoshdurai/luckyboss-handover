<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRecord;
use App\Models\AiUsage;
use App\Models\Company;
use App\Services\EntitlementCatalogue;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Credits and the switch that decides whether they bite.
 *
 * There is no payment gateway yet, so this screen is how credits reach a
 * company: an admin grants them. That is deliberate sequencing, not a gap —
 * the ledger and the metering can be trusted in production long before anyone
 * has agreed a price.
 *
 * The enforcement switch is the other half of sir's 2026-09-07 decision. With it
 * off — the default, and where it must stay until prices are signed off — every
 * action is still recorded but nothing is ever refused. Turning it on is what
 * "change it in the backend to paid" actually means.
 */
class EntitlementController extends Controller
{
    private function admin(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function index(EntitlementCatalogue $catalogue, SubscriptionEntitlementService $entitlements): View
    {
        $this->admin();

        $companies = Company::orderBy('name')->get();

        return view('admin.entitlements.index', [
            'catalogue' => $catalogue->skus(),
            'employerSkus' => $catalogue->for('employer'),
            'seekerSkus' => $catalogue->for('seeker'),
            'enforcing' => $catalogue->enforcementEnabled(),
            'companies' => $companies,
            'balances' => $companies->mapWithKeys(fn (Company $c) => [
                $c->id => $entitlements->summary($c, 'employer'),
            ]),
            // Spec §79 puts an AI Cost card on the admin dashboard beside
            // Employer Revenue. This is the number that says whether a package
            // priced at SGD 299 makes money — without it, an AI tier is being
            // sold blind.
            'aiCost' => $this->aiCost(),
        ]);
    }

    /**
     * What Lucky Boss AI has cost us this month and last, and what employers
     * ran on their own keys.
     *
     * BYOAI spend is shown separately rather than added in: those employers paid
     * their own provider, and folding it into our cost would make our cheapest
     * customers look like our most expensive.
     *
     * @return array<string, mixed>
     */
    private function aiCost(): array
    {
        $ours = fn ($from, $to) => (float) AiUsage::where('provider', 'lucky_boss')
            ->whereBetween('created_at', [$from, $to])
            ->sum('estimated_cost_usd');

        return [
            'this_month' => $ours(now()->startOfMonth(), now()),
            'last_month' => $ours(now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()),
            'calls_this_month' => AiUsage::where('created_at', '>=', now()->startOfMonth())->count(),
            'failed_this_month' => AiUsage::where('created_at', '>=', now()->startOfMonth())->where('status', 'failed')->count(),
            'byoai_calls_this_month' => AiUsage::where('provider', 'employer_byoai')->where('created_at', '>=', now()->startOfMonth())->count(),
            'by_feature' => AiUsage::where('provider', 'lucky_boss')
                ->where('created_at', '>=', now()->startOfMonth())
                ->selectRaw('feature, COUNT(*) as calls, SUM(estimated_cost_usd) as cost')
                ->groupBy('feature')
                ->orderByDesc('cost')
                ->get(),
        ];
    }

    public function grant(Request $request, EntitlementCatalogue $catalogue, SubscriptionEntitlementService $entitlements): RedirectResponse
    {
        $this->admin();

        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'key' => ['required', 'string', 'max:60'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        // Validated against the catalogue rather than a database table: a typo
        // would otherwise create a balance for a SKU nothing can ever spend.
        $sku = $catalogue->sku($data['key']);
        if ($sku === null || $sku['audience'] !== 'employer') {
            return back()->withErrors(['key' => 'That is not an employer credit we sell.']);
        }

        $company = Company::findOrFail($data['company_id']);

        $entitlements->grant(
            $company,
            $data['key'],
            $data['quantity'],
            'admin_grant',
            // Admin grants do not expire. Only the monthly free tier does.
            null,
            $data['note'] ?: 'Granted by '.auth()->user()->name
        );

        return back()->with('success', "Added {$data['quantity']} × {$sku['label']} to {$company->name}.");
    }

    public function updateEnforcement(Request $request): RedirectResponse
    {
        $this->admin();

        AdminRecord::updateOrCreate(
            ['module' => 'billing', 'slug' => 'entitlements'],
            [
                'name' => 'Entitlement enforcement',
                'description' => 'Whether a shortfall actually refuses a billable action',
                'payload' => ['enforcement_enabled' => $request->boolean('enforcement_enabled')],
                'is_active' => true,
            ]
        );

        return back()->with('success', $request->boolean('enforcement_enabled')
            ? 'Enforcement is ON. Employers without credits will now be refused.'
            : 'Enforcement is OFF. Usage is still recorded, but nothing is refused.');
    }
}
