<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionCheck
{
    /**
     * Check if the employer has an active subscription.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('employer')) {
            return $next($request);
        }

        $company = $user->companies()->first();

        if (!$company) {
            return redirect()->route('employer.dashboard')
                ->with('error', 'No company profile found. Please contact support.');
        }

        // Check for active subscription
        $hasActiveSubscription = $company->subscriptions()
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', today())
            ->exists();

        if (!$hasActiveSubscription) {
            // Allow access to certain routes even without subscription
            $allowedRoutes = [
                'employer.dashboard',
                'employer.portal', // billing section
                'employer.company-profile.update',
            ];

            $currentRoute = $request->route()?->getName();

            // Allow billing/subscription related portal sections
            if ($currentRoute === 'employer.portal' && in_array($request->route('section'), ['billing', 'company-profile'])) {
                return $next($request);
            }

            if (!in_array($currentRoute, $allowedRoutes)) {
                return redirect()->route('employer.dashboard')
                    ->with('error', 'Your subscription has expired. Please renew to access this feature.');
            }
        }

        return $next($request);
    }
}
