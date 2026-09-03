<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\FirebaseTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Exchanges a Firebase ID token for a Lucky Boss session.
 *
 * THE DIVISION OF LABOUR, because it has been misunderstood before:
 *
 *   Firebase answers exactly one question - "is this really the owner of this
 *   Google account / phone number?" It stores nothing about the candidate.
 *
 *   MySQL is the system of record. Every user, job, application, company and
 *   image lives here, which is what keeps the admin panel, the employer web
 *   portal and the AI matching working. This controller is the only bridge
 *   between the two.
 *
 * The client never tells us who it is. The email, phone and name below are read
 * from the *signed* token claims, not from the request body. A client that
 * posted its own email alongside somebody else's token would otherwise take
 * over their account.
 */
class FirebaseAuthController extends Controller
{
    public function __construct(private readonly FirebaseTokenVerifier $verifier)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            // Which app is asking. Scopes the Sanctum token to one role, so a
            // seeker token cannot be replayed against employer endpoints.
            'app' => ['required', 'in:seeker,employer'],
            // Only used when creating a brand new account, and only when the
            // token itself carries no name (a phone sign-in has no display
            // name). Never overwrites an existing user's name.
            'name' => ['nullable', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2'],
            // Employer signup needs a company to attach to.
            'company_name' => ['nullable', 'string', 'max:180'],
        ]);

        try {
            $claims = $this->verifier->verify($data['id_token']);
        } catch (RuntimeException $e) {
            // Logged in full, reported generically. The specific reason
            // ("audience is not this project") tells an attacker which check
            // they failed.
            Log::warning('[FirebaseAuth] rejected token', ['reason' => $e->getMessage()]);

            return response()->json([
                'message' => 'We could not verify that sign-in. Please try again.',
            ], 401);
        }

        $role = $data['app'] === 'employer' ? 'employer' : 'job-seeker';
        $provider = $this->verifier->provider($claims);

        try {
            [$user, $created] = DB::transaction(
                fn () => $this->resolveUser($claims, $provider, $role, $data)
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! $user->hasRole($role)) {
            return response()->json([
                'message' => $role === 'employer'
                    ? 'This account is registered as a job seeker, not an employer.'
                    : 'This account is registered as an employer, not a job seeker.',
            ], 403);
        }

        if (property_exists($user, 'is_active') && $user->is_active === false) {
            return response()->json(['message' => 'This account has been deactivated.'], 403);
        }

        return response()->json([
            'token' => $user->createToken($role.'-mobile', [$role])->plainTextToken,
            'user' => $user->fresh(),
            'role' => $role,
            'auth_provider' => $provider,
            'is_new_user' => $created,
        ], $created ? 201 : 200);
    }

    /**
     * Finds the existing user for this Firebase identity, or creates one.
     *
     * @return array{0:User,1:bool} the user, and whether it was just created
     */
    private function resolveUser(array $claims, string $provider, string $role, array $data): array
    {
        $uid = $claims['sub'];
        $email = $claims['email'] !== null ? mb_strtolower(trim($claims['email'])) : null;
        $phone = $claims['phone_number'];

        // 1. Already linked. The normal path for every sign-in after the first.
        $user = User::where('firebase_uid', $uid)->first();
        if ($user !== null) {
            $this->backfill($user, $email, $phone);

            return [$user, false];
        }

        // 2. Not linked, but we already know this person by email or phone -
        //    typically someone who registered with a password months ago and is
        //    now tapping "Sign in with Google". Link the identity to the row
        //    they already own rather than creating a second, empty account they
        //    cannot find their applications in.
        //
        //    Email is only trusted as a matching key when Firebase says it is
        //    verified. Google always verifies; an unverified address would let
        //    somebody claim an account by signing up to a provider with a
        //    victim's email address.
        if ($email !== null && $claims['email_verified']) {
            $user = User::where('email', $email)->first();
        }
        if ($user === null && $phone !== null) {
            $user = User::where('phone', $phone)->first();
        }

        if ($user !== null) {
            $user->forceFill([
                'firebase_uid' => $uid,
                'auth_provider' => $provider,
            ])->save();

            $this->backfill($user, $email, $phone);

            return [$user, false];
        }

        // 3. The address or number is taken by an account we are not allowed to
        //    link to — reached when the provider did not verify the email, so
        //    step 2 deliberately refused to match on it.
        //
        //    Without this the code fell through to createUser() and hit the
        //    unique index, and the raw SQLSTATE message (including the full
        //    INSERT statement) was returned to the client. Refuse cleanly and
        //    tell them the one thing that actually helps.
        if ($email !== null && User::where('email', $email)->exists()) {
            throw new RuntimeException(
                'An account already uses that email address. Sign in with your '
                .'email and password instead.'
            );
        }
        if ($phone !== null && User::where('phone', $phone)->exists()) {
            throw new RuntimeException(
                'An account already uses that phone number. Sign in with your '
                .'email and password instead.'
            );
        }

        // 4. Brand new person.
        return [$this->createUser($claims, $provider, $role, $data), true];
    }

    private function createUser(array $claims, string $provider, string $role, array $data): User
    {
        $email = $claims['email'] !== null ? mb_strtolower(trim($claims['email'])) : null;

        // Name, in order of trustworthiness: the signed Google display name,
        // then whatever the signup form collected, then the local part of the
        // email. A phone-only signup that supplied no name gets an empty string
        // rather than an invented one - the profile screen prompts for it, and
        // showing a candidate a name they never entered is the fake-data
        // problem this project has had before.
        $name = $claims['name']
            ?? ($data['name'] ?? null)
            ?? ($email !== null ? explode('@', $email)[0] : '');

        $user = User::create([
            'name' => trim((string) $name),
            'email' => $email,
            'phone' => $claims['phone_number'],
            'country_code' => strtoupper($data['country_code'] ?? 'SG'),
            // No password column value at all. There is no password in a
            // Firebase sign-in, and a random hash would make "forgot password"
            // appear to work on an account that has none.
            'password' => null,
        ]);

        $user->forceFill([
            'firebase_uid' => $claims['sub'],
            'auth_provider' => $provider,
            // Google has already proven the address. Marking it verified here
            // stops the app asking the candidate to confirm an email Google
            // just confirmed.
            'email_verified_at' => ($email !== null && $claims['email_verified']) ? now() : null,
        ])->save();

        $roleId = Role::where('slug', $role)->value('id');
        if ($roleId !== null) {
            $user->roles()->attach($roleId);
        }

        if ($role === 'job-seeker') {
            CandidateProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'country_code' => strtoupper($data['country_code'] ?? 'SG'),
                    'profile_completion' => 10,
                ]
            );
        } else {
            $company = Company::create([
                'name' => $data['company_name'] ?? ($name !== '' ? $name : 'New Company'),
                'email' => $email,
                'phone' => $claims['phone_number'],
                'country_code' => strtoupper($data['country_code'] ?? 'SG'),
                'status' => 'pending',
            ]);
            $company->users()->attach($user->id, [
                'company_role' => 'company-admin',
                'is_active' => true,
            ]);
        }

        return $user;
    }

    /**
     * Fills in contact details the account was missing.
     *
     * Someone who signed up by phone and later adds Google gains an email, and
     * vice versa. Existing values are never overwritten - the candidate may
     * have deliberately changed their email in the profile screen, and the
     * Google address is not more authoritative than their own edit.
     */
    private function backfill(User $user, ?string $email, ?string $phone): void
    {
        $changes = [];

        if ($email !== null && ($user->email === null || $user->email === '')
            && ! User::where('email', $email)->whereKeyNot($user->getKey())->exists()) {
            $changes['email'] = $email;
        }

        if ($phone !== null && ($user->phone === null || $user->phone === '')
            && ! User::where('phone', $phone)->whereKeyNot($user->getKey())->exists()) {
            $changes['phone'] = $phone;
        }

        if ($changes !== []) {
            $user->forceFill($changes)->save();
        }
    }
}
