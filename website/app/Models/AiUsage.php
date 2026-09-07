<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    protected $table = 'ai_usage_log';

    protected $fillable = [
        'company_id', 'user_id', 'job_id', 'candidate_id', 'feature', 'source',
        'provider', 'model', 'prompt_tokens', 'completion_tokens',
        'estimated_cost_usd', 'status',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            // Decimal, not float: fractions of a cent per call add up across a
            // month, and float drift in money is not worth the argument.
            'estimated_cost_usd' => 'decimal:6',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
