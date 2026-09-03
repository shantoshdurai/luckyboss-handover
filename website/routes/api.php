<?php

use App\Http\Controllers\Api\V1\AppSettingsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmployerAiController;
use App\Http\Controllers\Api\V1\EmployerInsightsController;
use App\Http\Controllers\Api\V1\EmployerJobController;
use App\Http\Controllers\Api\V1\FirebaseAuthController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\PortalController;
use App\Http\Controllers\Api\V1\ProfilePhotoController;
use App\Http\Controllers\Api\V1\ResumeParseController;
use App\Http\Controllers\Api\V1\SeekerProfileController;
use App\Http\Controllers\Api\V1\SkillController;
use App\Http\Controllers\Api\V1\NavigationController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/ai-chat', \App\Http\Controllers\Api\AiChatController::class);

Route::prefix('v1')->group(function (): void {
    Route::post('/webhooks/payments/{gateway}', [WebhookController::class,'payment'])->middleware('throttle:30,1');
    // What the apps may show. Read side of the admin Feature Control screen
    // (spec section 3). Unauthenticated: the app needs it before anyone signs
    // in, and it describes the product, not a person. Hiding a button is never
    // the control — every gated endpoint still checks the flag itself.
    Route::get('/app-settings', AppSettingsController::class);

    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/job-seekers/register', [AuthController::class, 'registerSeeker']);
    Route::post('/auth/employers/register', [AuthController::class, 'registerEmployer']);
    // Firebase sign-in (Google / phone OTP) exchanged for a Sanctum token.
    // Both mobile apps post here; the `app` field scopes the issued token.
    // Rate limited like /auth/login: the endpoint creates users, so an
    // unthrottled caller could fill the table.
    Route::post('/auth/firebase', FirebaseAuthController::class)->middleware('throttle:10,1');
    // Skill taxonomy. Public: the onboarding wizard runs these before a
    // candidate has finished creating their profile, and they expose no user data.
    Route::get('/skills/search', [SkillController::class, 'search'])->middleware('throttle:120,1');
    Route::get('/skills/suggested', [SkillController::class, 'suggested'])->middleware('throttle:60,1');
    Route::post('/skills/related', [SkillController::class, 'related'])->middleware('throttle:60,1');

    Route::get('/jobs', [JobController::class, 'index'])->name('api.v1.jobs.index');
    Route::get('/jobs/{job}', [JobController::class, 'show'])->name('api.v1.jobs.show');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/navigation', NavigationController::class);
        Route::get('/job-seeker/dashboard', [PortalController::class, 'seekerDashboard']);
        Route::get('/job-seeker/insights', [AppSettingsController::class, 'seekerInsights']);
        // The profile the app reads on launch and writes on every edit. Without
        // these the wizard's answers lived only in memory.
        Route::get('/job-seeker/profile', [SeekerProfileController::class, 'show']);
        Route::put('/job-seeker/profile', [SeekerProfileController::class, 'update']);
        Route::post('/job-seeker/jobs/{job}/apply', [PortalController::class, 'apply']);
        // Vision parsing is gated on two admin flags inside the controller —
        // see spec section 93 on server-side entitlement checks.
        Route::post('/resume/parse', ResumeParseController::class)->middleware('throttle:10,1');
        Route::post('/job-seeker/photo', [ProfilePhotoController::class, 'store']);
        Route::delete('/job-seeker/photo', [ProfilePhotoController::class, 'destroy']);
        Route::get('/employer/dashboard', [PortalController::class, 'employerDashboard']);
        // Both sat OUTSIDE this group, so $request->user() was always null and
        // anyone could publish a vacancy with no token at all — filed under
        // whichever company happened to be first in the table.
        Route::post('/jobs', [PortalController::class, 'postEmployerJob']);
        Route::post('/employer/jobs', [PortalController::class, 'postEmployerJob']);
        // Ported from the live deployment (Thirumoorthy-a/luckybossapp), which
        // had three capabilities this tree never grew: listing an employer's own
        // vacancies, editing one after posting, and uploading a company logo.
        // Overwriting the live site with this codebase would have destroyed
        // them, so they are merged in rather than lost.
        Route::get('/employer/jobs', [EmployerJobController::class, 'index']);
        Route::post('/employer/jobs/{job}', [EmployerJobController::class, 'update']);
        Route::post('/employer/company/logo', [MediaController::class, 'companyLogo']);

        // The AI the employer subscription is actually sold on. Every action
        // re-checks the plan through EmployerAiGate — spec section 93 requires
        // the entitlement to be enforced here, not by hiding a button in the
        // app. A denied request still answers, from the rule-based engine, and
        // never spends a Gemini call.
        // What the plan gives them and what it has produced. The portal sold a
        // subscription without showing what was in it, and a boost without ever
        // reporting what it did.
        Route::get('/employer/insights', [EmployerInsightsController::class, 'overview']);
        Route::get('/employer/jobs/{job}/insights', [EmployerInsightsController::class, 'job']);

        Route::get('/employer/ai/status', [EmployerAiController::class, 'status']);
        Route::post('/employer/ai/job-description', [EmployerAiController::class, 'jobDescription'])->middleware('throttle:20,1');
        Route::post('/employer/ai/interview-questions', [EmployerAiController::class, 'interviewQuestions'])->middleware('throttle:20,1');
        // Offer letters, interview invitations and status emails - spec section 3
        // names all three. Drafts only; nothing here sends anything.
        Route::post('/employer/ai/letter', [EmployerAiController::class, 'letter'])->middleware('throttle:30,1');
        Route::get('/employer/jobs/{job}/ai-shortlist', [EmployerAiController::class, 'shortlist'])->middleware('throttle:30,1');
        // Legacy alias: the live site's apps call this path for the candidate
        // photo. Our own /job-seeker/photo stays the canonical route.
        Route::post('/job-seeker/profile/photo', [MediaController::class, 'candidatePhoto']);
        Route::get('/employer/candidates', [PortalController::class, 'employerCandidates']);
        Route::get('/employer/jobs/{job}/candidates', [PortalController::class, 'employerCandidates']);
        Route::put('/employer/candidates/{application}/status', [PortalController::class, 'updateCandidateStatus']);
        Route::post('/fcm-token', function (Request $request) {
            $request->validate([
                'fcm_token' => 'required|string',
                'device_type' => 'nullable|string|max:30',
            ]);
            $request->user()->update([
                'fcm_token' => $request->input('fcm_token'),
                'device_type' => $request->input('device_type', 'android'),
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'FCM device token registered successfully.',
            ]);
        });
    });
});
