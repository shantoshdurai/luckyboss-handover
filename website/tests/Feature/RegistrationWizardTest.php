<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The registration forms after they were split into steps.
 *
 * The steps are presentation only — one form, one POST, validation unchanged —
 * so the important thing to pin down is that nothing about registering actually
 * changed, and that a rejected submission reopens on the panel holding the
 * error. Without that last part a failed submit looks like a dead button: the
 * message is rendered on a step the person cannot see.
 */
class RegistrationWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_job_seeker_can_still_register_in_one_post(): void
    {
        $this->seed();
        $country = Country::where('is_active', true)->firstOrFail();

        $this->post(route('register.seeker.store'), [
            'name' => 'Ravi Kumar',
            'phone' => '+6591230000',
            'country_code' => $country->code,
            'email' => 'ravi@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => 'on',
        ])->assertRedirect(route('seeker.home'));

        $this->assertDatabaseHas('users', ['email' => 'ravi@example.test']);
        $this->assertTrue(User::where('email', 'ravi@example.test')->firstOrFail()->hasRole('job-seeker'));
    }

    public function test_an_employer_can_still_register_in_one_post(): void
    {
        $this->seed();
        $country = Country::where('is_active', true)->firstOrFail();

        $this->post(route('register.employer.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@acme.test',
            'phone' => '+6591230001',
            'company_name' => 'Acme Test Corp',
            'country_code' => $country->code,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => 'on',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'jane@acme.test']);
        $this->assertTrue(Company::where('name', 'Acme Test Corp')->exists());
    }

    public function test_every_field_is_still_rendered_so_the_form_works_without_javascript(): void
    {
        $this->seed();

        // The panels are hidden by the script, never by the server. If a field
        // stopped being rendered, anyone without JavaScript could not complete
        // registration at all — and the single POST would be missing values the
        // validator requires.
        // `phone` is posted as two controls — a dialling-code select and the
        // local digits — and stitched back together in the form request, so it
        // is `phone_national` and `dial_code` that have to be on the page.
        $employer = $this->get(route('register.employer'))->assertOk();
        foreach (['name', 'email', 'dial_code', 'phone_national', 'company_name',
                  'company_type_id', 'country_code', 'registration_number',
                  'password', 'password_confirmation', 'terms'] as $field) {
            $employer->assertSee('name="'.$field.'"', false);
        }

        $seeker = $this->get(route('register.seeker'))->assertOk();
        foreach (['name', 'dial_code', 'phone_national', 'email', 'password',
                  'password_confirmation', 'terms'] as $field) {
            $seeker->assertSee('name="'.$field.'"', false);
        }

        // Country is deliberately not asked at sign-up any more — sir cut it.
        // It is collected in the profile wizard, where matching actually uses it.
        $seeker->assertDontSee('name="country_code"', false);
    }

    public function test_a_job_seeker_can_register_without_a_country(): void
    {
        $this->seed();

        $this->post(route('register.seeker.store'), [
            'name' => 'No Country',
            'phone' => '+6591239999',
            'email' => 'nocountry@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => 'on',
        ])->assertRedirect(route('seeker.home'));

        $user = User::where('email', 'nocountry@example.test')->firstOrFail();
        $this->assertNull($user->candidateProfile->country_code);
    }

    public function test_the_register_entry_asks_which_kind_of_account(): void
    {
        $this->seed();

        // It used to redirect employers straight into the job seeker form.
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('What kind of account?')
            ->assertSee(route('register.seeker'), false)
            ->assertSee(route('register.employer'), false);
    }

    public function test_a_rejected_submission_reopens_on_the_step_holding_the_error(): void
    {
        $this->seed();
        $country = Country::where('is_active', true)->firstOrFail();

        // Everything valid except the password confirmation, which lives on the
        // employer form's third step.
        $this->from(route('register.employer'))
            ->post(route('register.employer.store'), [
                'name' => 'Jane Doe',
                'email' => 'jane2@acme.test',
                'phone' => '+6591230002',
                'company_name' => 'Acme Two',
                'country_code' => $country->code,
                'password' => 'password123',
                'password_confirmation' => 'does-not-match',
                'terms' => 'on',
            ])
            ->assertRedirect(route('register.employer'))
            ->assertSessionHasErrors('password');

        $this->followingRedirects()
            ->get(route('register.employer'))
            ->assertOk()
            ->assertSee('data-wizard-start="3"', false);
    }

    public function test_a_first_step_error_reopens_on_the_first_step(): void
    {
        $this->seed();
        $country = Country::where('is_active', true)->firstOrFail();

        $existing = User::whereHas('roles', fn ($q) => $q->where('slug', 'job-seeker'))->firstOrFail();

        $this->from(route('register.seeker'))
            ->post(route('register.seeker.store'), [
                'name' => 'Someone Else',
                'phone' => $existing->phone,
                'country_code' => $country->code,
                'email' => 'unique@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'terms' => 'on',
            ])
            ->assertSessionHasErrors('phone');

        $this->followingRedirects()
            ->get(route('register.seeker'))
            ->assertOk()
            ->assertSee('data-wizard-start="1"', false);
    }
}
