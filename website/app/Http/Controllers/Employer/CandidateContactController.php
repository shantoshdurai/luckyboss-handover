<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\CandidateContactReveal;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Showing a candidate's phone number and email to an employer.
 *
 * Behind its own POST and its own confirm, which is spec §72, and not because
 * of the credit: a worker's phone number leaving our database is a thing that
 * should take a deliberate act, and a details panel that reveals itself on
 * hover is not one.
 *
 * §73 decides whether it costs anything. A candidate who applied to this
 * employer came to them, so their number is free — we only charge for people
 * Lucky Boss went and found. Getting that backwards would mean billing an
 * employer for reading their own inbox.
 *
 * Today the charge is nil for everybody: `candidate_view` is a zero-priced SKU
 * and enforcement is off, so `consume()` records the use and always returns
 * true. That is the point of doing it now — when charging is switched on, the
 * ledger already says who looked at whom, and nothing here changes.
 */
class CandidateContactController extends Controller
{
    public function reveal(Request $request, User $candidate, SubscriptionEntitlementService $entitlements): RedirectResponse
    {
        $employer = $this->employer();
        $company = $employer->companies()->first();

        abort_unless($company !== null, 403);
        abort_unless($candidate->hasRole('job-seeker'), 404);

        $existing = CandidateContactReveal::where('company_id', $company->id)
            ->where('candidate_id', $candidate->id)
            ->first();

        // Already held. Not a second charge, and not an error either — an
        // employer re-opening a shortlist is the normal case.
        if ($existing) {
            return back()->with('info', "You already have {$candidate->name}'s contact details.");
        }

        $organic = $this->appliedToUs($candidate, $company);

        if (! $organic && ! $entitlements->consume($employer, 'candidate_view', 1, $candidate, 'Contact revealed from a shortlist')) {
            return back()->withErrors([
                'reveal' => 'You are out of candidate views this month. An admin can add more.',
            ]);
        }

        CandidateContactReveal::create([
            'company_id' => $company->id,
            'candidate_id' => $candidate->id,
            'revealed_by' => $employer->id,
            'was_charged' => ! $organic,
            'reason' => $organic ? 'organic' : 'sourced',
        ]);

        return back()->with('success', $organic
            ? "{$candidate->name} applied to you, so their details are free."
            : "Here are {$candidate->name}'s details.");
    }

    /**
     * Did this candidate come to this employer under their own steam?
     *
     * Any application to any vacancy of theirs counts, including a withdrawn
     * one. Someone who applied and changed their mind still chose to make
     * contact, and charging for that would be charging for our own history.
     */
    private function appliedToUs(User $candidate, Company $company): bool
    {
        return JobApplication::where('candidate_id', $candidate->id)
            ->whereHas('job', fn ($q) => $q->where('company_id', $company->id))
            ->exists();
    }

    private function employer(): User
    {
        $user = auth()->user();
        abort_unless($user && $user->hasRole('employer'), 403);

        return $user;
    }
}
