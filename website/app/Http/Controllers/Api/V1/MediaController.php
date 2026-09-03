<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\HandlesApiUploads;
use App\Http\Controllers\Controller;
use App\Http\Resources\CandidateProfileResource;
use App\Http\Resources\CompanyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    use HandlesApiUploads;

    /** POST /v1/job-seeker/profile/photo */
    public function candidatePhoto(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasRole('job-seeker'), 403);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();
        $profile = $user->candidateProfile()->firstOrCreate(
            [],
            ['country_code' => $user->country_code ?? 'SG', 'profile_completion' => 20]
        );

        $this->deletePublicUpload($profile->profile_photo_path);

        $path = $this->storePublicUpload($request->file('photo'), 'candidates', 'profile');

        $profile->update([
            'profile_photo_path' => $path,
            'profile_completion' => max((int) $profile->profile_completion, 40),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile picture updated successfully.',
            'profile_photo_url' => asset($path),
            'url' => asset($path),
            'profile' => new CandidateProfileResource($profile->fresh()),
        ]);
    }

    /** POST /v1/employer/company/logo */
    public function companyLogo(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasRole('employer'), 403);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:4096'],
        ]);

        $company = $request->user()->companies()->firstOrFail();

        $this->deletePublicUpload($company->logo_path);

        $path = $this->storePublicUpload($request->file('logo'), 'companies', 'company');

        $company->update(['logo_path' => $path]);

        return response()->json([
            'status' => 'success',
            'message' => 'Company logo updated successfully.',
            'logo_url' => asset($path),
            'url' => asset($path),
            'company' => new CompanyResource($company->fresh()),
        ]);
    }
}
