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

    /**
     * How the platform decides which vacancies a candidate is shown, and how
     * many of them they may apply to at once.
     *
     * `minimum_match_score` is the number sir asked for: show me only jobs
     * fitting above 70, 80, 90 percent. It is admin-set rather than hard-coded
     * because the right threshold differs by market and by trade - an
     * Electrician in a thin local market needs a lower bar than a software
     * tester in Singapore.
     *
     * `bulk_apply_limit` caps one Apply All tap. Without a cap a single tap can
     * put one candidate in front of every employer on the platform at once,
     * which reads as spam from the employer side and is the fastest way to lose
     * the employers we are selling subscriptions to.
     *
     * @return array{minimum_match_score:int, bulk_apply_enabled:bool, bulk_apply_limit:int, auto_apply_enabled:bool}
     */
    public function matching(): array
    {
        $defaults = [
            'minimum_match_score' => 60,
            'bulk_apply_enabled' => true,
            'bulk_apply_limit' => 25,
            // Off until a human has watched it run. Applying on someone's
            // behalf while they are not looking is not a default.
            'auto_apply_enabled' => false,
        ];

        $payload = AdminRecord::where('module', 'matching')->where('slug', 'job-matching')->value('payload') ?? [];

        if (is_string($payload)) {
            $payload = json_decode($payload, true) ?: [];
        }

        if (! is_array($payload)) {
            $payload = [];
        }

        $merged = array_merge($defaults, $payload);

        return [
            // Clamped: an admin typo of 900 would empty every candidate's job
            // list with no visible cause.
            'minimum_match_score' => max(0, min(95, (int) $merged['minimum_match_score'])),
            'bulk_apply_enabled' => (bool) $merged['bulk_apply_enabled'],
            'bulk_apply_limit' => max(1, min(100, (int) $merged['bulk_apply_limit'])),
            'auto_apply_enabled' => (bool) $merged['auto_apply_enabled'],
        ];
    }
}