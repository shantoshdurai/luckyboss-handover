<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EntitlementLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Balances and consumption for billable actions, on both sides of the platform.
 *
 * `allows()` is the original boolean gate and is unchanged — several callers
 * still ask "does this plan include BYO AI?", which is a feature question, not a
 * quantity one. What is new is the quantity half: `balance()` and `consume()`.
 *
 * Three rules, and breaking any of them recreates the problem it was written to
 * avoid:
 *
 *  1. **Seekers run this same path**, with SKUs priced at zero and marked
 *     unlimited. There is no `if (seeker) skip billing` branch, because that
 *     branch is what would make sir's "flip them to paid later" a rewrite.
 *  2. **Unlimited still writes its consumption row.** A free action that records
 *     nothing gives us no basis to price it later, which was the whole point of
 *     metering from day one.
 *  3. **Enforcement is a separate switch from metering.** Until prices are
 *     signed off, `consume()` records and returns true. See
 *     EntitlementCatalogue::enforcementEnabled().
 */
class SubscriptionEntitlementService
{
    public function __construct(private readonly EntitlementCatalogue $catalogue) {}

    /** The original feature gate: does this company's active plan include $feature? */
    public function allows(Company $company, string $feature): bool
    {
        $subscription = $company->subscriptions()
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', today())
            ->latest('expires_at')
            ->first();

        return (bool) data_get($subscription?->entitlements, $feature, false);
    }

    /**
     * How many of $key the owner has left.
     *
     * **Null means unlimited** — never a large integer. Callers must handle null
     * explicitly; a `$balance < $n` check against null would silently refuse.
     */
    public function balance(Model $owner, string $key): ?int
    {
        $sku = $this->catalogue->sku($key);

        if ($sku === null || $sku['unlimited']) {
            return null;
        }

        // A package may itself grant unlimited use of one key — the seeded
        // Enterprise plan stores -1 for exactly this. Checked before summing,
        // because no finite total can represent it.
        if ($this->planAllowance($owner, $key) === -1) {
            return null;
        }

        $this->grantFreeTier($owner, $key);
        $this->grantPlanAllowance($owner, $key);

        return (int) EntitlementLedger::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->where('key', $key)
            ->live()
            ->sum('delta');
    }

    /**
     * Take $quantity of $key, recording it either way.
     *
     * Returns false only when enforcement is on and the balance is short. The
     * whole quantity is taken or none of it is: a bulk apply that half-charges
     * is worse than one that is refused outright.
     */
    public function consume(Model $owner, string $key, int $quantity = 1, ?Model $reference = null, ?string $note = null): bool
    {
        if ($quantity < 1) {
            return true;
        }

        $sku = $this->catalogue->sku($key);
        $unlimited = $sku === null || $sku['unlimited'];

        return DB::transaction(function () use ($owner, $key, $quantity, $reference, $note, $unlimited): bool {
            if (! $unlimited && $this->catalogue->enforcementEnabled()) {
                // A null balance here means unlimited — either the SKU or the
                // company's package. It must never be read as zero.
                $available = $this->balance($owner, $key) ?? PHP_INT_MAX;

                if ($available < $quantity) {
                    return false;
                }
            }

            $this->write($owner, $key, -$quantity, 'consumption', $reference, null, null, $note);

            return true;
        });
    }

    /** A credit an admin hands out directly. No gateway exists yet, so this is how credits arrive. */
    public function grant(Model $owner, string $key, int $quantity, string $source = 'admin_grant', ?\DateTimeInterface $expiresAt = null, ?string $note = null): EntitlementLedger
    {
        return $this->write($owner, $key, abs($quantity), $source, null, $expiresAt, null, $note);
    }

    /**
     * Everything the owner holds, ready for a "Name / Remaining" table.
     *
     * @return array<string, array{label:string, remaining:?int, unlimited:bool, free_tier_monthly:?int, unit:string}>
     */
    public function summary(Model $owner, string $audience): array
    {
        $rows = [];

        foreach ($this->catalogue->for($audience) as $key => $sku) {
            $rows[$key] = [
                'label' => $sku['label'],
                'remaining' => $this->balance($owner, $key),
                'unlimited' => $sku['unlimited'],
                'free_tier_monthly' => $sku['free_tier_monthly'],
                'unit' => $sku['unit'],
            ];
        }

        return $rows;
    }

    /** @return \Illuminate\Support\Collection<int, EntitlementLedger> */
    public function history(Model $owner, int $limit = 50)
    {
        return EntitlementLedger::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * The active subscription for a company, or null.
     *
     * Falls back to the package's own entitlements when the subscription row
     * does not carry a snapshot — `SubscriptionController@assign` copies them,
     * but a manually created row may not.
     */
    private function activeSubscription(Model $owner)
    {
        if (! $owner instanceof Company) {
            return null;
        }

        return $owner->subscriptions()
            ->with('package')
            ->where('status', 'active')
            ->whereDate('expires_at', '>=', today())
            ->latest('expires_at')
            ->first();
    }

    /**
     * What this owner's package includes for $key each period.
     *
     * Returns null when there is no package entitlement at all, and **-1 for
     * unlimited** — which is how the seeded Enterprise plan stores it. -1 is a
     * sentinel from existing data, not a design choice; `balance()` converts it
     * to the null the rest of the code means by "unlimited".
     */
    private function planAllowance(Model $owner, string $key): ?int
    {
        $packageKey = $this->catalogue->sku($key)['package_key'] ?? null;

        if ($packageKey === null) {
            return null;
        }

        $subscription = $this->activeSubscription($owner);

        if ($subscription === null) {
            return null;
        }

        $entitlements = $subscription->entitlements ?: ($subscription->package?->entitlements ?? []);
        $value = data_get($entitlements, $packageKey);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Issues this month's package allowance, once per period.
     *
     * Spec §33 makes limits a property of the package with a validity, so the
     * grant **expires** — at the end of the month, or when the subscription
     * itself lapses, whichever comes first. That expiry is the whole difference
     * between a plan allowance and a purchased pack: a lapsed plan's unused
     * allowance stops counting, a bought pack keeps counting.
     */
    private function grantPlanAllowance(Model $owner, string $key): void
    {
        $allowance = $this->planAllowance($owner, $key);

        if ($allowance === null || $allowance <= 0) {
            return;
        }

        $period = now()->format('Y-m');

        $exists = EntitlementLedger::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->where('key', $key)
            ->where('source', 'plan_grant')
            ->where('period', $period)
            ->exists();

        if ($exists) {
            return;
        }

        $subscription = $this->activeSubscription($owner);
        $endOfMonth = now()->endOfMonth();
        $expiry = $subscription?->expires_at && $subscription->expires_at->lt($endOfMonth)
            ? $subscription->expires_at
            : $endOfMonth;

        $this->write($owner, $key, $allowance, 'plan_grant', null, $expiry, $period, $subscription?->package?->name.' plan');
    }

    /**
     * Issues this calendar month's free allowance, once.
     *
     * Written lazily on first read rather than by a scheduled job, so it cannot
     * be missed on a deployment with no worker running — which this one does not
     * have yet.
     */
    private function grantFreeTier(Model $owner, string $key): void
    {
        $sku = $this->catalogue->sku($key);
        $allowance = $sku['free_tier_monthly'] ?? null;

        if (! $allowance) {
            return;
        }

        $period = now()->format('Y-m');

        $exists = EntitlementLedger::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->where('key', $key)
            ->where('source', 'free_tier')
            ->where('period', $period)
            ->exists();

        if ($exists) {
            return;
        }

        $this->write(
            $owner,
            $key,
            $allowance,
            'free_tier',
            null,
            // A monthly allowance expires with the month. A purchased pack does
            // not — that difference is the only thing separating the two billing
            // models sir may choose between.
            now()->endOfMonth(),
            $period,
            'Monthly free tier'
        );
    }

    private function write(Model $owner, string $key, int $delta, string $source, ?Model $reference, ?\DateTimeInterface $expiresAt, ?string $period, ?string $note): EntitlementLedger
    {
        return EntitlementLedger::create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'key' => $key,
            'delta' => $delta,
            'source' => $source,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'expires_at' => $expiresAt,
            'period' => $period,
            'note' => $note,
        ]);
    }
}
