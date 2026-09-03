<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    protected $table = 'ai_usage_log';

    protected $fillable = ['company_id', 'user_id', 'feature', 'source'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
