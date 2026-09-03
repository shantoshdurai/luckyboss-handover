<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\FirebaseTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Proves that a Firebase sign-in ends up stored correctly in MySQL.
 *
 * The signature check itself cannot be exercised here — that would need a
 * genuine Google-signed token, which only a real device sign-in produces. So
 * the verifier is swapped for a stub and these tests cover the half we own:
 * what gets written to the database once an identity is trusted, and what is
 * refused when it is not.
 *
 * The stub returns claims in exactly the shape the real verifier returns.
 */
class FirebaseAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['job-seeker', 'employer'] as $slug) {
            Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => ucfirst($slug), 'guard_name' => 'web']
            );
        }
    }

    /** Swaps in a verifier that accepts anything and returns fixed claims. */
    private function fakeVerifier(array $claims, string $provider = 'phone'): void
    {
        $this->app->instance(FirebaseTokenVerifier::class, new class($claims, $provider) extends FirebaseTokenVerifier
        {
            public function __construct(private array $claims, private string $prov)
            {
            }

            public function verify(string $idToken): array
            {
                return $this->claims + [
                    'email' => null,
                    'name' => null,
                    'phone_number' => null,
                    'email_verified' => false,
                    'picture' => null,
                    'firebase' => [],
                ];
            }

            public function provider(array $claims): string
            {
                return $this->prov;
            }
        });
    }

    public function test_a_phone_sign_in_creates_a_user_and_profile(): void
    {
        $this->fakeVerifier(['sub' => 'firebase-uid-1', 'phone_number' => '+919944995493']);

        $response = $this->postJson('/api/v1/auth/firebase', [
            'id_token' => 'stub',
            'app' => 'seeker',
            'country_code' => 'IN',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('role', 'job-seeker')
            ->assertJsonPath('is_new_user', true)
            ->assertJsonStructure(['token', 'user' => ['id']]);

        $user = User::where('firebase_uid', 'firebase-uid-1')->first();

        $this->assertNotNull($user, 'the Firebase identity must be stored');
        $this->assertSame('+919944995493', $user->phone);
        $this->assertSame('phone', $user->auth_provider);
        // A phone sign-in carries no email and no password. Both columns had to
        // become nullable for this; inventing values would put fake data in the
        // rows the employer portal displays.
        $this->assertNull($user->email);
        $this->assertNull($user->password);
        $this->assertTrue($user->hasRole('job-seeker'));
        $this->assertNotNull(
            CandidateProfile::where('user_id', $user->id)->first(),
            'a candidate profile must exist or the app has nowhere to save answers'
        );
    }

    public function test_signing_in_twice_reuses_the_same_user(): void
    {
        $this->fakeVerifier(['sub' => 'firebase-uid-1', 'phone_number' => '+6591234567']);

        $this->postJson('/api/v1/auth/firebase', ['id_token' => 'stub', 'app' => 'seeker'])
            ->assertStatus(201);

        $second = $this->postJson('/api/v1/auth/firebase', ['id_token' => 'stub', 'app' => 'seeker']);

        $second->assertStatus(200)->assertJsonPath('is_new_user', false);

        // Two rows for one person would split their applications across
        // accounts they cannot see.
        $this->assertSame(1, User::where('firebase_uid', 'firebase-uid-1')->count());
    }

    public function test_a_verified_google_email_links_to_an_existing_password_account(): void
    {
        $existing = User::create([
            'name' => 'Raja',
            'email' => 'raja@example.com',
            'password' => 'secret-password',
        ]);
        $existing->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        $this->fakeVerifier([
            'sub' => 'firebase-uid-google',
            'email' => 'raja@example.com',
            'email_verified' => true,
        ], 'google');

        $this->postJson('/api/v1/auth/firebase', ['id_token' => 'stub', 'app' => 'seeker'])
            ->assertStatus(200)
            ->assertJsonPath('is_new_user', false);

        $existing->refresh();

        $this->assertSame('firebase-uid-google', $existing->firebase_uid);
        $this->assertSame(1, User::where('email', 'raja@example.com')->count());
    }

    public function test_an_unverified_email_does_not_take_over_an_existing_account(): void
    {
        $victim = User::create([
            'name' => 'Raja',
            'email' => 'raja@example.com',
            'password' => 'secret-password',
        ]);
        $victim->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        // Same address, but the provider never verified it. Matching on this
        // would let anyone claim an account by signing up elsewhere with the
        // victim's email.
        $this->fakeVerifier([
            'sub' => 'attacker-uid',
            'email' => 'raja@example.com',
            'email_verified' => false,
        ], 'google');

        $response = $this->postJson('/api/v1/auth/firebase', ['id_token' => 'stub', 'app' => 'seeker']);

        $response->assertStatus(422);

        // The refusal must be a sentence a candidate can act on, never a
        // database error. This previously returned the raw SQLSTATE message
        // with the full INSERT statement in it.
        $this->assertStringContainsString('already uses that email', $response->json('message'));
        $this->assertStringNotContainsString('SQLSTATE', $response->json('message'));

        $victim->refresh();

        $this->assertNull($victim->firebase_uid, 'the existing account must not be linked');
        $this->assertSame(1, User::where('email', 'raja@example.com')->count());
        $this->assertSame(0, User::where('firebase_uid', 'attacker-uid')->count());
    }

    public function test_a_seeker_token_cannot_open_the_employer_app(): void
    {
        $this->fakeVerifier(['sub' => 'firebase-uid-1', 'phone_number' => '+6591234567']);

        $this->postJson('/api/v1/auth/firebase', ['id_token' => 'stub', 'app' => 'seeker'])
            ->assertStatus(201);

        // Same person, same token, now asking for an employer session.
        $this->postJson('/api/v1/auth/firebase', ['id_token' => 'stub', 'app' => 'employer'])
            ->assertStatus(403);
    }

    public function test_a_rejected_token_creates_nothing(): void
    {
        $this->app->instance(FirebaseTokenVerifier::class, new class extends FirebaseTokenVerifier
        {
            public function verify(string $idToken): array
            {
                throw new RuntimeException('Token signature is not valid.');
            }
        });

        $this->postJson('/api/v1/auth/firebase', ['id_token' => 'forged', 'app' => 'seeker'])
            ->assertStatus(401);

        $this->assertSame(0, User::whereNotNull('firebase_uid')->count());
    }
}
