<?php

namespace App\Services;

use App\Models\AiUsage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Spec §67 — one place that writes an AI usage row, with its cost.
 *
 * The reason this exists rather than each caller writing its own row: the cost
 * arithmetic has to be identical everywhere, or the AI Cost figure on the admin
 * dashboard (§79) is a number nobody can defend.
 *
 * **A failed call is still recorded.** The tokens were spent before the failure,
 * and a log that only counts successes understates what AI actually costs — which
 * is the exact number the pricing decision depends on.
 *
 * **BYOAI spend is recorded but costed at zero to us** (§5): the employer paid
 * their own provider. Counting it as our cost would make employers on their own
 * key look like our most expensive customers, when they are our cheapest.
 */
class AiUsageRecorder
{
    /**
     * Per-million-token prices, USD. Kept here rather than in config because
     * they change when a model changes, and the two belong together.
     *
     * These are list prices for the models we actually call. They are estimates
     * by definition — the column is named `estimated_cost_usd` for that reason —
     * and they are only ever used for *our* internal cost view, never to charge
     * a customer.
     */
    private const PRICES = [
        'gemini-2.5-flash' => ['in' => 0.30, 'out' => 2.50],
        'gemini-2.0-flash' => ['in' => 0.10, 'out' => 0.40],
        'gpt-4o-mini' => ['in' => 0.15, 'out' => 0.60],
    ];

    /** Used when a model is not in the table above, so an unknown model is not silently free. */
    private const FALLBACK = ['in' => 0.30, 'out' => 2.50];

    public function record(
        string $feature,
        ?User $user = null,
        ?string $model = null,
        int $promptTokens = 0,
        int $completionTokens = 0,
        string $status = 'success',
        string $provider = 'lucky_boss',
        ?int $jobId = null,
        ?int $candidateId = null,
    ): void {
        try {
            AiUsage::create([
                'company_id' => $user?->companies()->value('companies.id'),
                'user_id' => $user?->id,
                'job_id' => $jobId,
                'candidate_id' => $candidateId,
                'feature' => $feature,
                'source' => $provider,
                'provider' => $provider,
                'model' => $model,
                'prompt_tokens' => $promptTokens ?: null,
                'completion_tokens' => $completionTokens ?: null,
                'estimated_cost_usd' => $provider === 'lucky_boss'
                    ? $this->cost($model, $promptTokens, $completionTokens)
                    : 0,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            // Accounting must never break the feature it is measuring. A missing
            // usage row is a reporting gap; a thrown exception here would be a
            // candidate unable to upload their CV.
            Log::warning('[AiUsage] could not record usage: '.$e->getMessage());
        }
    }

    private function cost(?string $model, int $promptTokens, int $completionTokens): float
    {
        $rates = self::PRICES[$model] ?? self::FALLBACK;

        return round(
            ($promptTokens / 1_000_000) * $rates['in']
            + ($completionTokens / 1_000_000) * $rates['out'],
            6
        );
    }
}
