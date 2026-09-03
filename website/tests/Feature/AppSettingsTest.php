<?php

namespace Tests\Feature;

use App\Models\FeatureFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The apps need to know what the admin has switched on.
 *
 * Without this the AI button stayed on every phone after an admin turned AI
 * off. The server refused the call correctly, so nothing was insecure — the
 * candidate simply saw a feature that appeared broken.
 */
class AppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_apps_can_read_what_is_switched_on_without_signing_in(): void
    {
        FeatureFlag::create(['key' => 'platform_ai_enabled', 'name' => 'Platform AI', 'is_enabled' => true]);
        FeatureFlag::create(['key' => 'ai_resume_parser_enabled', 'name' => 'Resume Parser', 'is_enabled' => false]);

        // Unauthenticated on purpose: the app draws its sign-in screen before
        // anyone has an account, and these flags describe the product.
        $this->getJson('/api/v1/app-settings')
            ->assertOk()
            ->assertJsonPath('data.features.ai_assistant', true)
            ->assertJsonPath('data.features.resume_autofill', false);
    }

    public function test_turning_ai_off_is_visible_to_the_apps(): void
    {
        FeatureFlag::create(['key' => 'platform_ai_enabled', 'name' => 'Platform AI', 'is_enabled' => true]);

        $this->getJson('/api/v1/app-settings')
            ->assertJsonPath('data.features.ai_assistant', true);

        FeatureFlag::where('key', 'platform_ai_enabled')->update(['is_enabled' => false]);

        $this->getJson('/api/v1/app-settings')
            ->assertJsonPath('data.features.ai_assistant', false);
    }

    public function test_only_the_listed_flags_are_published(): void
    {
        FeatureFlag::create(['key' => 'platform_ai_enabled', 'name' => 'Platform AI', 'is_enabled' => true]);
        FeatureFlag::create([
            'key' => 'internal_billing_experiment',
            'name' => 'Internal experiment',
            'is_enabled' => true,
        ]);

        $features = $this->getJson('/api/v1/app-settings')->json('data.features');

        // The endpoint lists what it publishes rather than dumping the table,
        // so an internal flag added later is not broadcast to every handset.
        $this->assertArrayHasKey('ai_assistant', $features);
        $this->assertArrayNotHasKey('internal_billing_experiment', $features);
    }

    public function test_paid_applications_default_to_off_when_unseeded(): void
    {
        // Anything that costs a candidate money defaults to off if nobody has
        // deliberately switched it on. The rest default to on so a fresh
        // install is not a blank app.
        $features = $this->getJson('/api/v1/app-settings')->json('data.features');

        $this->assertFalse($features['paid_applications']);
        $this->assertTrue($features['ai_assistant']);
    }
}
