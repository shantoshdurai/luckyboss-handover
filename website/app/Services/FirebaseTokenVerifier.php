<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Verifies a Firebase ID token without any Composer dependency.
 *
 * The obvious implementation is kreait/firebase-php, but that pulls in a large
 * dependency tree and a service-account JSON file, and this project's
 * deployment rule is that the Laravel side runs on a plain PHP host with
 * nothing extra installed. Verification of an ID token needs none of that: the
 * token is a standard RS256 JWT and the signing certificates are served
 * publicly by Google. openssl_verify() is in PHP core.
 *
 * WHAT THIS CHECKS, AND WHY EACH ONE MATTERS
 *
 * Skipping any of these turns "verified" into "decoded", which is not a
 * security check at all - a JWT payload is plain base64 that anyone can write.
 *
 * - Signature against Google's current public certificate for the token's
 *   `kid`. This is what makes the token unforgeable.
 * - `aud` equals our Firebase project id. Without it, a token minted by any
 *   other Firebase project on earth - which anyone can create for free - would
 *   be accepted here.
 * - `iss` equals https://securetoken.google.com/<project id>.
 * - `exp` is in the future and `iat` is not. A stolen token stops working.
 * - `sub` is present and non-empty. It becomes the user's `firebase_uid`.
 *
 * A small clock skew allowance is applied to the time claims because the
 * handset's clock and the server's are not synchronised, and a phone a few
 * seconds fast otherwise produces a token that "was issued in the future".
 */
class FirebaseTokenVerifier
{
    /**
     * Google's public x509 certificates for Firebase ID tokens.
     * Rotated regularly; the response's Cache-Control max-age says for how
     * long, and we honour it rather than guessing.
     */
    private const CERT_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    private const CACHE_KEY = 'firebase_secure_token_certs';

    /** Tolerated difference between the handset clock and ours. */
    private const LEEWAY_SECONDS = 300;

    /**
     * Returns the verified claims, or throws.
     *
     * @throws RuntimeException with a message safe to log but NOT safe to show
     *                          a user verbatim - the controller maps it to a
     *                          generic failure.
     */
    public function verify(string $idToken): array
    {
        $projectId = (string) config('services.firebase.project_id');
        if ($projectId === '') {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        }

        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new RuntimeException('Malformed token: expected 3 segments.');
        }
        [$rawHeader, $rawPayload, $rawSignature] = $parts;

        $header = $this->decodeSegment($rawHeader, 'header');
        $claims = $this->decodeSegment($rawPayload, 'payload');

        // Firebase always signs ID tokens RS256. Accepting whatever the token
        // asks for is the classic JWT bypass: a token declaring "alg":"none"
        // would otherwise validate with no signature at all.
        if (($header['alg'] ?? null) !== 'RS256') {
            throw new RuntimeException('Unexpected signing algorithm.');
        }
        $kid = $header['kid'] ?? null;
        if (! is_string($kid) || $kid === '') {
            throw new RuntimeException('Token header has no key id.');
        }

        $certificate = $this->certificates()[$kid] ?? null;
        if ($certificate === null) {
            // A rotation we have not picked up yet is indistinguishable here
            // from a forged kid, so refresh once before rejecting.
            $certificate = $this->certificates(true)[$kid] ?? null;
        }
        if ($certificate === null) {
            throw new RuntimeException('Token signed with an unknown key.');
        }

        $publicKey = openssl_pkey_get_public($certificate);
        if ($publicKey === false) {
            throw new RuntimeException('Google certificate could not be parsed.');
        }

        $verified = openssl_verify(
            $rawHeader.'.'.$rawPayload,
            $this->base64UrlDecode($rawSignature),
            $publicKey,
            OPENSSL_ALGO_SHA256
        );

        if ($verified !== 1) {
            throw new RuntimeException('Token signature is not valid.');
        }

        $this->assertClaims($claims, $projectId);

        return [
            'sub' => (string) $claims['sub'],
            'email' => isset($claims['email']) ? (string) $claims['email'] : null,
            'name' => isset($claims['name']) ? (string) $claims['name'] : null,
            'phone_number' => isset($claims['phone_number']) ? (string) $claims['phone_number'] : null,
            'email_verified' => (bool) ($claims['email_verified'] ?? false),
            'picture' => isset($claims['picture']) ? (string) $claims['picture'] : null,
            'firebase' => (array) ($claims['firebase'] ?? []),
        ];
    }

    /**
     * Which Firebase sign-in method produced the token: google, phone, or
     * password. Taken from the `firebase.sign_in_provider` claim, which is set
     * by Google and covered by the signature - unlike anything the client could
     * tell us in the request body.
     */
    public function provider(array $claims): string
    {
        $raw = (string) ($claims['firebase']['sign_in_provider'] ?? '');

        return match ($raw) {
            'google.com' => 'google',
            'phone' => 'phone',
            default => 'password',
        };
    }

    private function assertClaims(array $claims, string $projectId): void
    {
        $now = time();

        if (($claims['aud'] ?? null) !== $projectId) {
            throw new RuntimeException('Token audience is not this Firebase project.');
        }
        if (($claims['iss'] ?? null) !== 'https://securetoken.google.com/'.$projectId) {
            throw new RuntimeException('Token issuer is not Firebase.');
        }
        if (! isset($claims['sub']) || ! is_string($claims['sub']) || $claims['sub'] === '') {
            throw new RuntimeException('Token has no subject.');
        }
        if (($claims['exp'] ?? 0) <= $now - self::LEEWAY_SECONDS) {
            throw new RuntimeException('Token has expired.');
        }
        if (($claims['iat'] ?? 0) > $now + self::LEEWAY_SECONDS) {
            throw new RuntimeException('Token was issued in the future.');
        }
        // auth_time is when the user actually authenticated. Firebase sets it
        // on every ID token; a token claiming a future authentication is
        // malformed rather than merely stale.
        if (isset($claims['auth_time']) && $claims['auth_time'] > $now + self::LEEWAY_SECONDS) {
            throw new RuntimeException('Token authentication time is in the future.');
        }
    }

    /**
     * @return array<string,string> kid => PEM certificate
     */
    private function certificates(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)->get(self::CERT_URL);
        } catch (\Throwable $e) {
            Log::error('[FirebaseTokenVerifier] certificate fetch failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('Could not reach Google to verify the sign-in.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Google returned '.$response->status().' for the signing certificates.');
        }

        $certificates = $response->json();
        if (! is_array($certificates) || $certificates === []) {
            throw new RuntimeException('Google returned no signing certificates.');
        }

        Cache::put(self::CACHE_KEY, $certificates, $this->certificateTtl($response->header('Cache-Control')));

        return $certificates;
    }

    /**
     * Honours Google's own max-age so a rotated key is not cached past its
     * life, with a floor and ceiling in case the header is missing or absurd.
     */
    private function certificateTtl(?string $cacheControl): int
    {
        if ($cacheControl !== null && preg_match('/max-age=(\d+)/', $cacheControl, $m) === 1) {
            return max(300, min((int) $m[1], 86400));
        }

        return 3600;
    }

    private function decodeSegment(string $segment, string $what): array
    {
        $decoded = json_decode($this->base64UrlDecode($segment), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Token '.$what.' is not valid JSON.');
        }

        return $decoded;
    }

    private function base64UrlDecode(string $value): string
    {
        $padded = strtr($value, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode($padded, true);
    }
}
