<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobBoost;
use App\Models\JobView;
use App\Services\EmployerAiGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What the employer's subscription gives them, and what it has produced.
 *
 * Two things were missing and they are the same problem. The portal sold a plan
 * without showing what was in it, and sold a boost (spec §61) without ever
 * reporting what it did. An employer had no way to answer "was that worth it?",
 * which is the only question a paying customer actually asks.
 *
 * Every number here is measured. Where nothing has been measured yet the
 * response says zero and the app says "no views yet" — it does not fill the
 * screen with a plausible figure. For a feature somebody paid for, an invented
 * number is worse than an empty one.
 */
class EmployerInsightsController extends Controller
{
    public function __construct(private readonly EmployerAiGate $gate)
    {
    }

    /**
     * The account overview: plan, what it unlocks, usage against its limits,
     * and the totals across every vacancy.
     */
    public function overview(Request $request): JsonResponse
    {
        $company = $this->company($request);

        if ($company === null) {
            return response()->json(['status' => 'error', 'message' => 'No company is linked to this account.'], 404);
        }

        $subscription = $company->subscriptions()
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', today())
            ->latest('expires_at')
            ->first();

        $entitlements = $subscription?->entitlements ?? [];
        $jobIds = Job::where('company_id', $company->id)->pluck('id');

        $activeJobs = Job::where('company_id', $company->id)->where('status', 'published')->count();
        $applications = JobApplication::whereIn('job_id', $jobIds)->count();
        $views = JobView::whereIn('job_id', $jobIds)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'company' => [
                    'name' => $company->name,
                    'status' => $company->status,
                    'verified' => $company->status === 'verified',
                ],
                'plan' => [
                    'name' => $subscription?->package?->name ?? 'No active plan',
                    'active' => $subscription !== null,
                    'expires_at' => $subscription?->expires_at?->toDateString(),
                    'days_remaining' => $subscription?->expires_at
                        ? max(0, (int) now()->startOfDay()->diffInDays($subscription->expires_at, false))
                        : null,
                ],
                // -1 means unlimited throughout, matching how the packages are
                // seeded. The app renders that as "Unlimited" rather than "-1".
                'limits' => [
                    'job_posts' => $this->limit($entitlements, 'job_posts', $activeJobs),
                    'candidate_views' => $this->limit($entitlements, 'candidate_views', 0),
                ],
                'ai' => $this->gate->summary($company),
                'totals' => [
                    'active_jobs' => $activeJobs,
                    'applications' => $applications,
                    'views' => $views,
                    // Honest about newness: nothing has been measured before
                    // view tracking existed, and a zero here means "not yet",
                    // not "nobody looked".
                    'tracking_since' => JobView::whereIn('job_id', $jobIds)->min('viewed_at'),
                ],
                'boosts' => [
                    'active' => JobBoost::where('company_id', $company->id)
                        ->where('status', 'active')
                        ->where('ends_at', '>=', now())
                        ->count(),
                    'total_spent' => (int) JobBoost::where('company_id', $company->id)->sum('amount'),
                    'currency' => JobBoost::where('company_id', $company->id)->value('currency') ?? 'SGD',
                ],
            ],
        ]);
    }

    /**
     * One vacancy: how many people saw it, how many applied, and — when a boost
     * is or was running — how the boosted days compare with the days before it.
     */
    public function job(Request $request, Job $job): JsonResponse
    {
        $company = $this->company($request);
        abort_unless($company !== null && $job->company_id === $company->id, 404);

        $views = JobView::where('job_id', $job->id)->count();
        $applications = JobApplication::where('job_id', $job->id)->count();

        $boost = JobBoost::where('job_id', $job->id)->latest('ends_at')->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'job' => [
                    'id' => $job->id,
                    'title' => $job->title,
                    'status' => $job->status,
                    'published_at' => $job->published_at?->toIso8601String(),
                ],
                'views' => $views,
                'applications' => $applications,
                // The number an employer actually judges a listing by. Guarded
                // against division by zero rather than reported as 0%, because
                // "no views yet" and "nobody applied" are different facts.
                'apply_rate' => $views > 0 ? round(($applications / $views) * 100, 1) : null,
                'daily' => $this->dailySeries($job),
                'boost' => $boost === null ? null : $this->boostReport($job, $boost),
            ],
        ]);
    }

    /**
     * Views per day for the last fortnight, so the app can draw a line and an
     * employer can see the shape rather than one total.
     */
    private function dailySeries(Job $job): array
    {
        $rows = JobView::where('job_id', $job->id)
            ->where('viewed_at', '>=', now()->subDays(13)->startOfDay())
            ->get(['viewed_at']);

        $counts = [];
        for ($i = 13; $i >= 0; $i--) {
            $counts[now()->subDays($i)->toDateString()] = 0;
        }

        foreach ($rows as $row) {
            $key = $row->viewed_at?->toDateString();
            if ($key !== null && array_key_exists($key, $counts)) {
                $counts[$key]++;
            }
        }

        return collect($counts)->map(fn ($n, $d) => ['date' => $d, 'views' => $n])->values()->all();
    }

    /**
     * What the boost did.
     *
     * Compared against the same number of days immediately before it started,
     * which is the only comparison available without a control group — and it
     * is stated as such rather than dressed up as a guarantee. When the vacancy
     * was not live long enough beforehand, `before` is null and the app says
     * there is nothing to compare with instead of showing a flattering
     * percentage against a day of silence.
     */
    private function boostReport(Job $job, JobBoost $boost): array
    {
        $days = max(1, (int) $boost->starts_at->diffInDays($boost->ends_at));

        $during = JobView::where('job_id', $job->id)
            ->whereBetween('viewed_at', [$boost->starts_at, min($boost->ends_at, now())])
            ->count();

        $windowStart = $boost->starts_at->copy()->subDays($days);
        $publishedBefore = $job->published_at !== null && $job->published_at <= $windowStart;

        $before = $publishedBefore
            ? JobView::where('job_id', $job->id)
                ->whereBetween('viewed_at', [$windowStart, $boost->starts_at])
                ->count()
            : null;

        return [
            'type' => $boost->type,
            'active' => $boost->is_active,
            'starts_at' => $boost->starts_at->toIso8601String(),
            'ends_at' => $boost->ends_at->toIso8601String(),
            'days_remaining' => $boost->is_active
                ? max(0, (int) now()->diffInDays($boost->ends_at, false))
                : 0,
            'amount' => $boost->amount,
            'currency' => $boost->currency,
            'views_during' => $during,
            'views_before' => $before,
            'applications_during' => JobApplication::where('job_id', $job->id)
                ->whereBetween('applied_at', [$boost->starts_at, min($boost->ends_at, now())])
                ->count(),
            'comparable' => $before !== null,
        ];
    }

    private function limit(array $entitlements, string $key, int $used): array
    {
        $allowed = data_get($entitlements, $key);

        return [
            'used' => $used,
            'allowed' => $allowed,
            'unlimited' => $allowed === -1,
        ];
    }

    private function company(Request $request): ?Company
    {
        return $request->user()?->companies()->first();
    }
}
