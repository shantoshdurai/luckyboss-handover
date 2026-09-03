<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FaviconSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_favicon_and_seo_settings_render_in_public_and_admin_heads(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.site-settings.update'), [
            'logo' => UploadedFile::fake()->create('logo.png', 50, 'image/png'),
            'favicon' => UploadedFile::fake()->create('favicon.png', 50, 'image/png'),
            'site_name' => 'Lucky Boss Recruitment',
            'seo_title' => 'Find Better Jobs | Lucky Boss',
            'seo_description' => 'A managed recruitment platform for employers and job seekers.',
            'primary_color' => '#1769e0',
            'secondary_color' => '#18a66a',
            'office_address' => 'Singapore',
            'official_email' => 'hello@luckyboss.test',
        ])->assertRedirect();

        $branding = AdminRecord::where('module', 'branding')->where('slug', 'website-branding')->value('payload');
        $this->assertNotEmpty($branding['favicon_url']);
        $this->get(route('home'))->assertOk()->assertSee($branding['favicon_url'])->assertSee('A managed recruitment platform for employers and job seekers.')->assertSee('application/ld+json');
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee($branding['favicon_url'])->assertSee('application/ld+json');
    }
}
