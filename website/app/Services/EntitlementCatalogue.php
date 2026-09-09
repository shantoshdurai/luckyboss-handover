<?php

namespace App\Services;

use App\Models\AdminRecord;

/**
 * What can be bought, what it costs, and what is free each month.
 *
 * Sir's decision, 2026-09-07: **employers are charged on a subscription;
 * seekers are free — but as zero-priced SKUs, not as a hardcoded exemption**, so
 * the backend can move them onto paid at any time. That is why seeker actions
 * appear here at all. There is deliberately no `if (seeker) skip billing` branch
 * anywhere in this codebase; that branch is precisely what would turn the later
 * flip into a rewrite instead of a settings change.
 *
 * Two consequences worth knowing before changing anything here:
 *
 *  - **Unlimited is `null`, never a large number.** A seeded 999,999 looks like
 *    "free" right up to the day someone hits it and is refused with no
 *    explanation. Flipping a seeker SKU to paid means writing an integer where a
 *    null was.
 *  - **Zero-priced SKUs stay invisible.** While a seeker SKU costs nothing, no
 *    seeker screen shows a price, a balance or a "0 remaining". Billing
 *    furniture appearing around something free is its own kind of lie. Turning
 *    it on later is a deliberate UI change.
 *
 * Prices are placeholders until sir signs them off (per market, SG/MY/IN — the
 * existing PackagePrice table already carries per-currency rows). Nothing reads
 * them for payment yet: there is no gateway, only admin-granted credits.
 */
class EntitlementCatalogue
{
    /**
     * @return array<string, array{label:string, audience:string, unit:string, price:int, currency:string, pack_size:int, free_tier_monthly:?int, unlimited:bool, description:string}>
     */
    public function skus(): array
    {
        return [
            // ── Employers ────────────────────────────────────────────────
            'job_post' => [
                'label' => 'Post a vacancy',
                'audience' => 'employer',
                'unit' => 'vacancy',
                'price' => 0,
                'currency' => 'SGD',
                'pack_size' => 1,
                'free_tier_monthly' => 1,
                'unlimited' => false,
                'package_key' => 'job_posts',
                'description' => 'One published vacancy on the Lucky Boss job board.',
            ],
            'candidate_view' => [
                'label' => 'View candidate contact details',
                'audience' => 'employer',
                'unit' => 'candidate',
                'price' => 0,
                'currency' => 'SGD',
                'pack_size' => 15,
                'free_tier_monthly' => 5,
                'unlimited' => false,
                'package_key' => 'candidate_views',
                // Spec §73: contacts are free for candidates who applied to this
                // employer directly. A credit is only spent on someone Lucky Boss
                // recommended or sourced — we charge for what we found, never for
                // what the employer earned themselves.
                'organic_is_free' => true,
                'description' => 'Unlock the phone and email of a candidate we sourced for you.',
            ],
            'ai_match' => [
                'label' => 'AI match report',
                'audience' => 'employer',
                'unit' => 'report',
                'price' => 0,
                'currency' => 'SGD',
                'pack_size' => 15,
                'free_tier_monthly' => 5,
                'unlimited' => false,
                'package_key' => 'ai_usage',
                'description' => 'A scored breakdown of one candidate against one vacancy.',
            ],

            // ── Job seekers: free today, metered from day one ─────────────
            'apply' => [
                'label' => 'Apply to a job',
                'audience' => 'seeker',
                'unit' => 'application',
                'price' => 0,
                'currency' => 'SGD',
                'pack_size' => 1,
                'free_tier_monthly' => null,
                'unlimited' => true,
                'description' => 'Free, and the last thing that would ever be priced.',
            ],
            'bulk_apply' => [
                'label' => 'Apply All',
                'audience' => 'seeker',
                'unit' => 'application',
                'price' => 0,
                'currency' => 'SGD',
                'pack_size' => 1,
                'free_tier_monthly' => null,
                'unlimited' => true,
                'description' => 'Free. Already capped per tap by the admin bulk-apply limit.',
            ],
            'resume_parse' => [
                'label' => 'Read my resume with AI',
                'audience' => 'seeker',
                'unit' => 'document',
                'price' => 0,
                'currency' => 'SGD',
                'pack_size' => 1,
                'free_tier_monthly' => null,
                'unlimited' => true,
                // Metered because it is one of the two seeker actions that costs
                // us real money per call. If seekers are ever priced, the case
                // will be built from these numbers.
                'description' => 'Free. Costs us Gemini spend per document, so usage is recorded.',
            ],
            'auto_apply' => [
                'label' => 'Auto-apply',
                'audience' => 'seeker',
                'unit' => 'application',
                'price' => 0,
                'currency' => 'SGD',
                'pack_size' => 15,
                'free_tier_monthly' => null,
                'unlimited' => true,
                // The one seeker SKU most likely to be priced first, and the
                // reason this catalogue exists at all. TickBig sells applies in
                // blocks of 15; `pack_size` carries that shape now so turning it
                // on later is three edits here and a flip of the enforcement
                // switch — not a rewrite of AutoApplyService.
                //
                // To charge for it: set 'unlimited' => false, give
                // 'free_tier_monthly' an integer (the "few free credits, then
                // pay" model), and enable enforcement. AutoApplyService already
                // reads the balance and stops at 'no_credits'; the seeker screen
                // already renders a remaining count when the SKU is finite.
                'description' => 'Free today. We apply to matching jobs for you while you are away.',
            ],
            'ai_chat' => [
                'label' => 'Ask Lucky AI',
                'audience' => 'seeker',
                'unit' => 'message',
                'price' => 0,
                'currency' => 'SGD',
                'pack_size' => 1,
                'free_tier_monthly' => null,
                'unlimited' => true,
                'description' => 'Free. Also costs real spend per message, so usage is recorded.',
            ],
        ];
    }

    /** @return array{label:string, audience:string, unit:string, price:int, currency:string, pack_size:int, free_tier_monthly:?int, unlimited:bool, description:string}|null */
    public function sku(string $key): ?array
    {
        return $this->skus()[$key] ?? null;
    }

    /** @return array<string, array<string, mixed>> */
    public function for(string $audience): array
    {
        return array_filter($this->skus(), fn (array $sku) => $sku['audience'] === $audience);
    }

    /**
     * Whether a shortfall actually refuses the action.
     *
     * **Off by default, and it must stay off until sir signs off prices.** With
     * it off, `consume()` still writes every ledger row — so the metering is
     * real and the history accrues — but nothing is ever blocked. This is the
     * switch that makes "zero rupees at the start, change it in the backend
     * later" a settings change rather than a release.
     */
    public function enforcementEnabled(): bool
    {
        $payload = AdminRecord::where('module', 'billing')->where('slug', 'entitlements')->value('payload') ?? [];

        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: [];
        }

        return (bool) data_get(is_array($payload) ? $payload : [], 'enforcement_enabled', false);
    }
}
