<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Candidate profile photo — spec section 31 ("Profile Photo" in the complete
 * resume profile).
 *
 * The photo arrives from either the device camera or its gallery; the app does
 * not tell us which, and it does not matter here. What matters is that nothing
 * the client claims about the file is trusted:
 *
 *   - The declared MIME type is ignored in favour of what the bytes actually
 *     decode as. A file named avatar.jpg containing PHP is rejected because it
 *     will not decode as an image, not because of its extension.
 *   - The image is re-encoded from raw pixels rather than stored as uploaded.
 *     This strips EXIF — which on a phone photo carries GPS coordinates of
 *     wherever the candidate took it — and neutralises any payload smuggled in
 *     metadata segments.
 *   - The stored filename is generated, never derived from the client's.
 */
class ProfilePhotoController extends Controller
{
    /**
     * POST /api/v1/job-seeker/photo (multipart, field: photo)
     */
    public function store(Request $request): JsonResponse
    {
        $config = config('luckyboss.profile_photo');

        $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:'.implode(',', $config['mimes']),
                'max:'.$config['max_kb'],
            ],
        ], [
            'photo.max' => 'That photo is too large. Please choose one under '
                .round($config['max_kb'] / 1024).' MB.',
            'photo.image' => 'That file is not an image.',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('photo');

        $encoded = $this->reencode($file, $config['max_dimension']);
        if ($encoded === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'That image could not be read. Please try a different photo.',
            ], 422);
        }

        $profile = CandidateProfile::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['country_code' => $request->user()->country_code ?? 'SG', 'profile_completion' => 20]
        );

        $disk = Storage::disk($config['disk']);
        $path = $config['path'].'/'.Str::uuid()->toString().'.jpg';
        $disk->put($path, $encoded, 'public');

        // Remove the previous file only after the replacement is safely stored,
        // so a failure mid-way leaves the candidate with their old photo rather
        // than none at all.
        $previous = $profile->profile_photo_path;
        $profile->update(['profile_photo_path' => $path]);
        if ($previous && $previous !== $path && $disk->exists($previous)) {
            $disk->delete($previous);
        }

        return response()->json([
            'status' => 'success',
            'path' => $path,
            'url' => $disk->url($path),
            'profile_completion' => $profile->profile_completion,
        ]);
    }

    /**
     * DELETE /api/v1/job-seeker/photo
     */
    public function destroy(Request $request): JsonResponse
    {
        $profile = CandidateProfile::where('user_id', $request->user()->id)->first();
        $disk = Storage::disk(config('luckyboss.profile_photo.disk'));

        if ($profile?->profile_photo_path) {
            if ($disk->exists($profile->profile_photo_path)) {
                $disk->delete($profile->profile_photo_path);
            }
            $profile->update(['profile_photo_path' => null]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Decodes the upload and re-encodes it as a square-safe JPEG.
     *
     * Returns null when the bytes do not decode as an image, which is the real
     * content check — an attacker controls the extension and the declared MIME
     * type, but cannot make arbitrary bytes survive a GD decode/encode round
     * trip as executable content.
     */
    private function reencode(UploadedFile $file, int $maxDimension): ?string
    {
        $raw = file_get_contents($file->getRealPath());
        if ($raw === false) {
            return null;
        }

        // Suppressed because a malformed upload is an expected input here, not
        // an exceptional condition; the null return is the error channel.
        $image = @imagecreatefromstring($raw);
        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest > $maxDimension) {
            $scale = $maxDimension / $longest;
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
            if ($resized !== false) {
                imagedestroy($image);
                $image = $resized;
            }
        }

        // Flatten onto white: a transparent PNG avatar re-encoded to JPEG
        // without this renders with a black background.
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, 85);
        $encoded = ob_get_clean();

        imagedestroy($image);
        imagedestroy($canvas);

        return $encoded === false ? null : $encoded;
    }
}
