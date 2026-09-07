<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ResumeIntakeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Resume parsing — POST /api/v1/resume/parse
 *
 * The pipeline itself now lives in App\Services\ResumeIntakeService, shared with
 * the web portal's resume-first onboarding. Two copies would drift, and the half
 * that drifted would be the one that starts inventing employment history.
 *
 * Two gates, both server-side, because spec §93 is explicit that hiding a button
 * in Flutter is not a control:
 *   1. `platform_ai_enabled` — the master AI switch (spec §3).
 *   2. `ai_resume_parser_enabled` — vision parsing specifically, so an admin can
 *      run the chatbot while leaving document upload off.
 *
 * With either off this still returns 200 and still keeps the file; only the
 * autofill is withheld. It never returns invented data: a resume parser that
 * guesses puts a fabricated work history on a real person's profile and sends it
 * to employers.
 */
class ResumeParseController extends Controller
{
    public function __invoke(Request $request, ResumeIntakeService $intake): JsonResponse
    {
        $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:'.ResumeIntakeService::MAX_KB],
        ], [
            'resume.mimes' => 'Upload a PDF or Word document.',
            'resume.max' => 'That file is too large. Keep it under 4 MB.',
        ]);

        $result = $intake->intake($request->file('resume'), $request->user());

        // The wire format predates the service and Flutter reads it, so it is
        // assembled here rather than returned wholesale: `requires_review` and
        // `data` appear only on success, and `message` only when they do not.
        $resume = [
            'file_name' => $result['resume']['file_name'],
            'url' => $result['resume']['url'],
            'stored' => $result['resume']['stored'],
        ];

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                // Explicitly flagged as unverified. The client must present
                // these as values to check, not as though the candidate typed
                // them.
                'requires_review' => true,
                'data' => $result['data'],
                'resume' => $resume,
            ]);
        }

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'],
            'resume' => $resume,
        ]);
    }
}
