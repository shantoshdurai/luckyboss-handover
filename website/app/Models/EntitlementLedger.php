<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One line of the entitlement ledger: a grant (positive) or a use (negative).
 *
 * Rows are append-only by convention. Correcting a mistake means writing a
 * compensating row, not editing history — the point of a ledger is that it can
 * be read back to explain a balance.
 */
class EntitlementLedger extends Model
{
    protected $table = 'entitlement_ledger';

    protected $fillable = [
        'owner_type', 'owner_id', 'key', 'delta', 'source',
        'reference_type', 'reference_id', 'expires_at', 'period', 'note',
    ];

    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /** Rows that still count towards a balance. */
    public function scopeLive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
        });
    }
}
