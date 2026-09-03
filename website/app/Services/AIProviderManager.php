<?php

namespace App\Services;

use App\Models\ApiIntegration;
use Illuminate\Support\Facades\Crypt;

class AIProviderManager
{
    public function available(string $feature): bool
    {
        $integration = ApiIntegration::query()
            ->where('key', $feature)
            ->first();

        if (! $integration || ! $integration->is_enabled) {
            return false;
        }

        $secret = $integration->encrypted_secret
            ? Crypt::decryptString($integration->encrypted_secret)
            : null;

        return filled($secret) && ! empty(trim($secret));
    }

    public function fallbackMatch(): array
    {
        return ['provider' => 'rule-based', 'available' => false, 'message' => 'AI is unavailable; the rule-based recruitment workflow remains active.'];
    }
}