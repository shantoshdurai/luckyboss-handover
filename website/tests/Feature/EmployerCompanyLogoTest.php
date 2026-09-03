<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\SiteSettingsService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployerCompanyLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_employer_company_logo_upload_is_saved_and_rendered(): void
    {
        $this->seed();
        $employer = User::where('email', 'employer@luckyboss.test')->firstOrFail();
        $company = $employer->companies()->firstOrFail();

        $this->actingAs($employer)->put(route('employer.company-profile.update'), [
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'country_code' => $company->country_code,
            'logo' => UploadedFile::fake()->create('company-logo.png', 100, 'image/png'),
        ])->assertRedirect();

        $company->refresh();
        $this->assertNotEmpty($company->logo_path);
        $this->assertFileExists(public_path($company->logo_path));
        $response = $this->actingAs($employer)->get(route('employer.portal', 'profile'))->assertOk()->assertSee(asset($company->logo_path));
        $brandUrl = app(SiteSettingsService::class)->branding()['logo_url'];
        $response->assertSee($brandUrl);
    }
}
