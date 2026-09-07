<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\EntitlementCatalogue;
use App\Services\SubscriptionEntitlementService;
use Illuminate\View\View;

/**
 * What this company can do, and how much of it is left.
 *
 * Two tabs, taken straight from TickBig's structure because it is the right
 * shape: **Subscription** is what you can buy, **Your Current Plan** is a plain
 * Name / Remaining table. Their version answers "how many do I have left" in one
 * glance with no chart and no upgrade nag, and that is exactly the question an
 * employer opens this page with.
 *
 * What is deliberately not here yet: a cart and a payment gateway. Credits
 * currently arrive from the monthly free tier or from an admin grant, and prices
 * show as "not set" until sir signs them off per market. Shipping a Buy button
 * that cannot take money would be worse than showing none.
 */
class SubscriptionController extends Controller
{
    /** Mirrors Employer\JobController: an employer belongs to companies, not a company. */
    private function company(): Company
    {
        $user = auth()->user();
        $company = $user?->companies()->first();

        if ($company === null && $user?->hasRole('super-admin')) {
            $company = Company::first();
        }

        abort_unless($company !== null, 403);

        return $company;
    }

    public function index(EntitlementCatalogue $catalogue, SubscriptionEntitlementService $entitlements): View
    {
        $company = $this->company();

        return view('employer.subscription.index', [
            'company' => $company,
            'skus' => $catalogue->for('employer'),
            'balances' => $entitlements->summary($company, 'employer'),
            'history' => $entitlements->history($company, 40),
            // Shown plainly. While this is false nothing is ever refused, and
            // saying so is more honest than a page that implies a limit it does
            // not apply.
            'enforcing' => $catalogue->enforcementEnabled(),
        ]);
    }
}
