<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Models\FeatureFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AiApiController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'ai-dashboard';
        return view('admin.ai-api.index', ['view' => $view, 'integrations' => ApiIntegration::orderBy('name')->get(), 'flags' => FeatureFlag::orderBy('name')->get(), 'errors' => ApiIntegration::whereNotNull('last_error')->latest('updated_at')->get(), 'filters' => $request->only('view')]);
    }

    public function updateFlag(Request $request, FeatureFlag $flag): RedirectResponse
    {
        $this->ensureAdmin();

        $flag->update(['is_enabled' => $request->boolean('is_enabled')]);

        // FeatureFlagService caches each flag for five minutes. Without this an
        // admin switching AI off during an incident would watch it stay on for
        // another five minutes, which is exactly when a switch has to be
        // immediate.
        Cache::forget("feature-flag:{$flag->key}");

        return back()->with('success', 'Feature flag updated.');
    }

    public function updateIntegration(Request $request, ApiIntegration $integration): RedirectResponse
    {
        $this->ensureAdmin(); $integration->update(['is_enabled' => $request->boolean('is_enabled')]); return back()->with('success', 'Integration state updated.');
    }

    public function clearError(ApiIntegration $integration): RedirectResponse
    {
        $this->ensureAdmin(); $integration->update(['last_error' => null]); return back()->with('success', 'Integration error cleared.');
    }
}
