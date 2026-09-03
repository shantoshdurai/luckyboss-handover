<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BrandingLogoConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_brand_logo_is_rendered_across_portals(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $logo = UploadedFile::fake()->create('brand-logo.png', 100, 'image/png');

        $this->actingAs($admin)->put(route('admin.site-settings.update'), [
            'logo' => $logo,
            'favicon' => UploadedFile::fake()->create('favicon.png', 50, 'image/png'),
            'site_name' => 'Lucky Boss Portal',
            'seo_title' => 'Lucky Boss Recruitment',
            'seo_description' => 'Find jobs and manage recruitment with Lucky Boss.',
            'primary_color' => '#1769e0',
            'secondary_color' => '#18a66a',
            'office_address' => 'Singapore',
            'official_email' => 'brand@luckyboss.test',
        ])->assertRedirect();

        $logoUrl = AdminRecord::where('module', 'branding')->where('slug', 'website-branding')->value('payload')['logo_url'];
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee($logoUrl);
        $this->get(route('employers.public'))->assertOk()->assertSee($logoUrl);

        $employer = User::where('email', 'employer@luckyboss.test')->firstOrFail();
        $this->actingAs($employer)->get(route('employer.dashboard'))->assertOk()->assertSee($logoUrl);

        $seeker = User::where('email', 'candidate@luckyboss.test')->firstOrFail();
        $this->actingAs($seeker)->get(route('seeker.dashboard'))->assertOk()->assertSee($logoUrl);
    }
}
