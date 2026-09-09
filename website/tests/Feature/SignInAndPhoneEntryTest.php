<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two defects sir hit on the same afternoon, both of them the page answering a
 * question on his behalf and then holding him to the answer.
 *
 * 1. Sign-in refused correct credentials. The "I am a" toggle pre-selects
 *    "Job seeker" and used to be a hard gate, so an employer who typed the
 *    right email and password was signed straight back out and told their
 *    account was not registered as a job seeker.
 *
 * 2. The phone box was free text behind a "+65 8000 0000" placeholder, and the
 *    number that came out of it was "91+6383515761". The code is a control now.
 */
class SignInAndPhoneEntryTest extends TestCase
{
    use RefreshDatabase;

    private function accountWithRole(string $roleSlug, string $email): User
    {
        $user = User::factory()->create(['email' => $email, 'password' => 'password123']);
        $user->roles()->attach(Role::where('slug', $roleSlug)->value('id'));

        return $user;
    }

    public function test_an_employer_signs_in_even_with_the_job_seeker_toggle_selected(): void
    {
        $this->seed();
        $this->accountWithRole('employer', 'gate@acme.test');

        // Exactly what the form posts when nobody touches the toggle.
        $this->post(route('login.store'), [
            'email'    => 'gate@acme.test',
            'password' => 'password123',
            'login_as' => 'job-seeker',
        ])->assertRedirect(route('employer.home'));

        $this->assertAuthenticated();
    }

    public function test_a_job_seeker_signs_in_with_the_employer_toggle_selected(): void
    {
        $this->seed();
        $this->accountWithRole('job-seeker', 'gate@seeker.test');

        $this->post(route('login.store'), [
            'email'    => 'gate@seeker.test',
            'password' => 'password123',
            'login_as' => 'employer',
        ])->assertRedirect(route('seeker.home'));

        $this->assertAuthenticated();
    }

    public function test_the_toggle_still_chooses_the_landing_for_someone_holding_both_roles(): void
    {
        $this->seed();
        $user = $this->accountWithRole('job-seeker', 'both@acme.test');
        $user->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $this->post(route('login.store'), [
            'email'    => 'both@acme.test',
            'password' => 'password123',
            'login_as' => 'job-seeker',
        ])->assertRedirect(route('seeker.home'));

        $this->post(route('login.store'), [
            'email'    => 'both@acme.test',
            'password' => 'password123',
            'login_as' => 'employer',
        ])->assertRedirect(route('employer.home'));
    }

    public function test_a_wrong_password_is_still_refused(): void
    {
        $this->seed();
        $this->accountWithRole('employer', 'gate@acme.test');

        $this->post(route('login.store'), [
            'email'    => 'gate@acme.test',
            'password' => 'not-the-password',
            'login_as' => 'employer',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_dialling_code_defaults_to_india_and_can_be_changed(): void
    {
        $this->seed();

        $page = $this->get(route('register.seeker'))->assertOk();
        $page->assertSee('value="+91" selected', false);
        $page->assertSee('value="+65"', false);
        $page->assertSee('value="+60"', false);
    }

    public function test_the_code_and_the_local_number_are_stored_as_one_phone(): void
    {
        $this->seed();

        $this->post(route('register.seeker.store'), [
            'name'                  => 'Santosh',
            'dial_code'             => '+91',
            'phone_national'        => '6383515761',
            'email'                 => 'santosh@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => 'on',
        ])->assertRedirect(route('seeker.home'));

        $this->assertDatabaseHas('users', [
            'email' => 'santosh@example.test',
            'phone' => '+916383515761',
        ]);
    }

    public function test_spacing_and_a_trunk_zero_do_not_create_a_second_account(): void
    {
        $this->seed();

        $this->post(route('register.seeker.store'), [
            'name'                  => 'Spaced Out',
            'dial_code'             => '+65',
            'phone_national'        => '0 8000 0001',
            'email'                 => 'spaced@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => 'on',
        ])->assertRedirect(route('seeker.home'));

        $this->assertDatabaseHas('users', ['phone' => '+6580000001']);

        // The same subscriber typed differently must collide with the row above
        // rather than becoming a duplicate the employer cannot ring.
        $this->post(route('register.seeker.store'), [
            'name'                  => 'Same Number',
            'dial_code'             => '+65',
            'phone_national'        => '80000001',
            'email'                 => 'other@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => 'on',
        ])->assertSessionHasErrors('phone');
    }

    public function test_an_employer_registers_through_the_split_phone_field(): void
    {
        $this->seed();
        $country = Country::where('is_active', true)->firstOrFail();

        $this->post(route('register.employer.store'), [
            'name'                  => 'Jane Boss',
            'email'                 => 'jane@split.test',
            'dial_code'             => '+60',
            'phone_national'        => '123456789',
            'company_name'          => 'Split Corp',
            'country_code'          => $country->code,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => 'on',
        ])->assertRedirect(route('employer.home'));

        $this->assertDatabaseHas('users', ['email' => 'jane@split.test', 'phone' => '+60123456789']);
    }

    public function test_a_blank_number_is_reported_rather_than_stored_empty(): void
    {
        $this->seed();

        $this->post(route('register.seeker.store'), [
            'name'                  => 'No Phone',
            'dial_code'             => '+91',
            'phone_national'        => '',
            'email'                 => 'nophone@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'terms'                 => 'on',
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('users', ['email' => 'nophone@example.test']);
    }
}
