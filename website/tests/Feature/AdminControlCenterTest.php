<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControlCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_permissions_audit_settings_and_system_pages_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $groups = [
            'analytics' => ['hiring-funnel', 'application-conversion', 'interview-conversion', 'offer-acceptance-rate', 'average-time-to-hire', 'best-job-categories', 'employer-activity', 'candidate-growth', 'revenue-by-country', 'revenue-by-currency', 'ai-usage-by-employer'],
            'users-permissions' => ['admin-users', 'admin-roles', 'permissions', 'activity-logs'],
            'audit-logs' => ['admin-activity', 'employer-activity', 'candidate-activity', 'api-changes', 'payment-changes', 'security-logs'],
            'settings' => ['portal-settings', 'branding', 'contact-information', 'seo', 'currency', 'tax', 'date-time', 'countries', 'languages', 'email-configuration', 'maintenance-mode', 'terms', 'privacy'],
            'system' => ['login-security', 'otp-settings', 'password-policy', 'session-settings', 'ip-blocking', 'failed-login-logs', 'api-rate-limits', 'file-upload-rules', 'import-export', 'system-logs', 'backup'],
        ];
        foreach ($groups as $section => $views) foreach ($views as $view) $this->actingAs($admin)->get(route('admin.command.show', [$section, $view]))->assertOk()->assertSee('Live controls');
    }
}
