<?php

namespace App\Http\Middleware;

use App\Services\EntitlementCatalogue;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spec §38 — what an employer keeps when their subscription expires.
 *
 * The spec is careful here and it is worth following exactly. On expiry the
 * company **still logs in**, and still reaches their profile, account settings,
 * subscription, billing, payment history and renewal. What closes is the
 * recruitment surface: job applications, candidate contacts, candidate resumes,
 * interview history and the candidate database.
 *
 * That asymmetry is the commercial point. A customer locked out entirely churns;
 * a customer who can still see their pipeline sitting there, just out of reach,
 * renews. This must never become a hard lockout.
 *
 * Two things changed when this was finally wired up:
 *
 *  1. **It is gated on the same enforcement switch as everything else.** While
 *     charging is off — the default — it does nothing at all. Without that
 *     guard, attaching this would instantly lock every existing employer out of
 *     their own candidates, because none of them has a paid subscription today.
 *     The previous version had no such guard and was attached to no routes,
 *     which is the only reason it never caused that.
 *  2. **It is applied per route rather than by an internal allow-list.** The
 *     restricted routes name this middleware; everything else is open by
 *     default. An allow-list fails dangerously — a route added later is
 *     restricted by accident, and nobody finds out until a customer cannot open
 *     their own billing page.
 */
class SubscriptionCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(EntitlementCatalogue::class)->enforcementEnabled()) {
            return $next($request);
        }

        $user = $request->user();

        // Super admins are not customers and hold no subscription of their own.
        if (! $user || ! $user->hasRole('employer') || $user->hasRole('super-admin')) {
            return $next($request);
        }

        $company = $user->companies()->first();

        if ($company === null) {
            return $next($request);
        }

        $active = $company->subscriptions()
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', today())
            ->exists();

        if ($active) {
            return $next($request);
        }

        return redirect()
            ->route('employer.subscription')
            ->with('info', 'Your subscription has expired. Your jobs and candidates are safe — renew to open them again.');
    }
}
