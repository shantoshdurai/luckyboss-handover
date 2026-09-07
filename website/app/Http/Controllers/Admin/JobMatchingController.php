<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRecord;
use App\Models\Job;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The four numbers that decide which vacancies a candidate is shown and how
 * many of them one tap may apply to.
 *
 * These have existed in SiteSettingsService::matching() since Apply All shipped,
 * but nothing ever wrote them, so every deployment silently ran on the defaults.
 * Sir asked for this screen directly: "80% மேல fit ஆகுற Job, 90% மேல fit ஆகுற
 * Job அப்படிங்கற மாதிரி நாம Admin setup பண்ணிடுவோம்."
 *
 * Kept on its own route rather than folded into SiteSettingsController because
 * that controller's update() is CSRF-exempt. Whatever the reason for that on
 * branding, it is not a property we want on the switch that can auto-apply on a
 * candidate's behalf.
 */
class JobMatchingController extends Controller
{
    private function admin(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function edit(SiteSettingsService $settings): View
    {
        $this->admin();

        return view('admin.job-matching.edit', [
            'matching' => $settings->matching(),
            // Context for the person setting the threshold. Without it the
            // number is abstract - 80 means nothing until you know there are
            // 14 published jobs to apply it to.
            'publishedJobs' => Job::where('status', 'published')->count(),
            'seekerCount' => User::whereHas('roles', fn ($q) => $q->where('slug', 'job-seeker'))->count(),
        ]);
    }

    public function update(Request $request, SiteSettingsService $settings): RedirectResponse
    {
        $this->admin();

        $data = $request->validate([
            // Bounds match the clamp in SiteSettingsService::matching(). 95 is
            // the ceiling on purpose: at 100 a candidate sees an empty list and
            // no explanation, because our scorer caps the headline score by how
            // much of the profile it could actually compare.
            'minimum_match_score' => ['required', 'integer', 'min:0', 'max:95'],
            'bulk_apply_limit' => ['required', 'integer', 'min:1', 'max:100'],
        ], [
            'minimum_match_score.max' => 'The threshold cannot go above 95%. Our scorer caps a match by how much of the profile it could compare, so 100% is not reachable for most candidates and the list would come back empty.',
        ]);

        $payload = [
            'minimum_match_score' => (int) $data['minimum_match_score'],
            'bulk_apply_enabled' => $request->boolean('bulk_apply_enabled'),
            'bulk_apply_limit' => (int) $data['bulk_apply_limit'],
            'auto_apply_enabled' => $request->boolean('auto_apply_enabled'),
        ];

        AdminRecord::updateOrCreate(
            ['module' => 'matching', 'slug' => 'job-matching'],
            [
                'name' => 'Job Matching & Apply All',
                'description' => 'Match threshold, bulk apply cap, and auto-apply switch',
                'payload' => $payload,
                'is_active' => true,
            ]
        );

        // Read back through the service so the confirmation quotes what will
        // actually be applied, not what was posted.
        $saved = $settings->matching();

        return back()->with('success', "Saved. Candidates are now shown jobs matching {$saved['minimum_match_score']}% or better, and Apply All is capped at {$saved['bulk_apply_limit']} jobs per tap.");
    }
}
