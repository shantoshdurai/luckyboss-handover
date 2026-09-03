<?php

namespace App\Services;

use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    public function enabled(string $key): bool
    {
        return Cache::remember("feature-flag:{$key}", now()->addMinutes(5), fn () =>
            (bool) FeatureFlag::query()->where('key', $key)->value('is_enabled')
        );
    }
}