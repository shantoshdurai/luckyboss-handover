<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What the apps are allowed to show, and how the candidate is doing.
 *
 * Until now neither app asked the server what was switched on. The admin could
 * turn the AI copilot off and the button stayed on every phone, doing nothing
 * useful when tapped — the server refused correctly, but the candidate just saw
 * a feature that appeared broken.
 *
 * This is the read side of the admin's Feature Control screen (spec §3). It is
 * a convenience for the interface only: hiding a button is not a security
 * control, and every gated endpoint still checks the flag itself. Spec §93 is
 * explicit about that, and nothing here weakens it.
 *
 * Deliberately unauthenticated. The app needs to know whether to draw a sign-in
 * option before anyone has signed in, and these flags describe the product, not
 * any person.
 */
class AppSettingsController extends Controller
{
    /**
     * Flags the apps actually branch on. Listed explicitly rather than dumping
     * the table, so an internal flag added later is not published to every
     * handset by accident.
     */
    private const PUBLIC_FLAGS = [
        'platform_ai_enabled' => 'ai_assistant',
        'ai_matching_enabled' => 'job_matching',
        'ai_resume_parser_enabled' => 'resume_autofill',
        'external_jobs_enabled' => 'partner_jobs',
        'candidate_monetization_enabled' => 'paid_applications',
        'google_calendar_enabled' => 'calendar',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $flags = FeatureFlag::whereIn('key', array_keys(self::PUBLIC_FLAGS))
            ->pluck('is_enabled', 'key');

        $features = [];
        foreach (self::PUBLIC_FLAGS as $key => $name) {
            // Absent means not yet seeded, and the safe reading of that is
            // "off" for anything that costs money and "on" for anything that
            // merely displays. Only paid applications default to off.
            $features[$name] = (bool) ($flags[$key] ?? ($name === 'paid_applications' ? false : true));
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'features' => $features,
                // Lets the app warn about a build that is too old for the
                // server it is talking to, rather than failing field by field.
                'api_version' => 'v1',
            ],
        ]);
    }

    /**
     * The candidate's own numbers.
     *
     * Only what is genuinely recorded. There is no "employers viewed your
     * profile" figure here because nothing measures that yet, and a made-up one
     * would be the most tempting number on the screen and the least true.
     */
    public function seekerInsights(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user?->candidateProfile;

        $applications = JobApplication::where('candidate_id', $user?->id);

        $byStage = (clone $applications)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'status' => 'success',
            'data' => [
                'profile_completion' => (int) ($profile?->profile_completion ?? 0),
                'applications' => [
                    'total' => (clone $applications)->count(),
                    'by_stage' => $byStage,
                    'last_applied_at' => (clone $applications)->max('applied_at'),
                ],
                'resume_on_file' => filled($profile?->resume_path),
                'skills_count' => is_array($profile?->skills) ? count($profile->skills) : 0,
            ],
        ]);
    }
}
