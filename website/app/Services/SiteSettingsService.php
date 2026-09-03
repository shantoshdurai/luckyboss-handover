<?php

namespace App\Services;

use App\Models\AdminRecord;

class SiteSettingsService
{
    public function branding(): array
    {
        $defaults = [
            'logo_url' => asset('images/lucky-boss-logo-transparent.png'), 'favicon_url' => asset('images/lucky-boss-logo.png'), 'site_name' => 'Luckyboss Employment Agency Pte. Ltd', 'seo_title' => 'Luckyboss Employment Agency Pte. Ltd | AI-Powered Recruitment', 'seo_description' => 'Find jobs, build your career, and manage recruitment with Luckyboss Employment Agency Pte. Ltd.', 'primary_color' => '#1769e0', 'secondary_color' => '#18a66a',
        ];

        $payload = AdminRecord::where('module', 'branding')->where('slug', 'website-branding')->value('payload') ?? [];

        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: [];
        }

        if (! is_array($payload)) {
            $payload = [];
        }

        $branding = array_merge($defaults, $payload);
        foreach (['logo_url', 'favicon_url'] as $key) {
            if (is_string($branding[$key] ?? null) && str_starts_with($branding[$key], 'http://localhost/')) {
                $branding[$key] = ltrim(parse_url($branding[$key], PHP_URL_PATH) ?: '', '/');
            }
        }

        // A configured logo that is not on disk leaves a broken image in the
        // header of every page. The stored setting pointed at
        // images/luckyboss-logo.svg, a file this project has never contained,
        // so the site rendered its own alt text where the logo should be.
        //
        // Checked rather than trusted, because the value is admin-editable and
        // a typo there should not deface the site. A remote URL is left alone;
        // only local paths can be verified here.
        foreach (['logo_url' => 'images/lucky-boss-logo-transparent.png',
                  'favicon_url' => 'images/lucky-boss-logo.png'] as $key => $fallback) {
            $value = $branding[$key] ?? null;

            if (! is_string($value) || $value === '') {
                $branding[$key] = asset($fallback);
                continue;
            }

            if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                continue;
            }

            if (! file_exists(public_path(ltrim($value, '/')))) {
                $branding[$key] = asset($fallback);
            }
        }

        return $branding;
    }

    public function contact(): array
    {
        $payload = AdminRecord::where('module', 'contact-information')->where('slug', 'official-contact')->value('payload') ?? [
            'office_address' => 'Singapore', 'official_email' => 'hello@luckyboss.test', 'official_phone' => '', 'facebook_url' => 'https://www.facebook.com/', 'instagram_url' => 'https://www.instagram.com/', 'linkedin_url' => 'https://www.linkedin.com/', 'youtube_url' => 'https://www.youtube.com/', 'whatsapp_url' => 'https://wa.me/',
        ];

        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: [];
        }

        if (! is_array($payload)) {
            $payload = [];
        }

        return array_merge([
            'office_address' => 'Singapore', 'official_email' => 'hello@luckyboss.test', 'official_phone' => '', 'facebook_url' => 'https://www.facebook.com/', 'instagram_url' => 'https://www.instagram.com/', 'linkedin_url' => 'https://www.linkedin.com/', 'youtube_url' => 'https://www.youtube.com/', 'whatsapp_url' => 'https://wa.me/',
        ], $payload);
    }
}