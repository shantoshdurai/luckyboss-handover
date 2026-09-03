<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_job_pages_render_seeded_jobs_and_categories(): void
    {
        $this->seed();
        $this->get(route('jobs.index'))->assertOk()->assertSee('Warehouse Supervisor');
        $this->get(route('jobs.index', ['country' => 'SG', 'location' => 'Singapore']))->assertOk()->assertSee('Warehouse Supervisor');
        $this->get(route('categories.index'))->assertOk()->assertSee('Construction');
        $this->get(route('employers.public'))->assertOk()->assertSee('Subscription Plans');
    }

    public function test_navigation_api_returns_job_seeker_menu(): void
    {
        $this->seed();
        $candidate = User::where('email', 'candidate@luckyboss.test')->firstOrFail();
        Sanctum::actingAs($candidate, ['job-seeker']);

        $this->getJson('/api/v1/navigation')
            ->assertOk()
            ->assertJsonPath('area', 'job-seeker')
            ->assertJsonFragment(['label' => 'Find Jobs']);
    }

    public function test_admin_can_open_branding_settings_and_public_contact_page(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.site-settings.edit'))->assertOk()->assertSee('Official Contact');
        $this->get(route('contact.public'))->assertOk()->assertSee('Contact Us');
    }

    public function test_footer_uses_working_internal_and_configured_external_links(): void
    {
        $this->seed();
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('jobs.index').'"', false)
            ->assertSee('href="https://www.facebook.com/"', false)
            ->assertSee('href="https://wa.me/"', false);
    }
}