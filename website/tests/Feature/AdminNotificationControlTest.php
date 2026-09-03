<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_notification_modules_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        foreach (['notification-dashboard', 'notification-types', 'push-notifications', 'email-notifications', 'whatsapp-notifications', 'admin-alerts', 'employer-alerts', 'job-seeker-alerts', 'notification-sounds', 'notification-history'] as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['notifications', $view]))->assertOk()->assertSee('Live controls');
            $this->actingAs($admin)->get(route('admin.notifications.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_create_and_delete_sound_and_notification(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.notifications.sounds.store'), ['name' => 'Success Tone', 'description' => 'Success notification'])->assertRedirect();
        $sound = AdminRecord::where('module', 'notification-sounds')->where('name', 'Success Tone')->firstOrFail();
        $this->assertDatabaseHas('admin_records', ['id' => $sound->id, 'module' => 'notification-sounds']);
        $notification = PlatformNotification::create(['user_id' => $admin->id, 'type' => 'admin', 'title' => 'Test alert', 'body' => 'Alert body']);
        $this->actingAs($admin)->delete(route('admin.notifications.destroy', $notification))->assertRedirect();
        $this->assertDatabaseMissing('platform_notifications', ['id' => $notification->id]);
        $this->actingAs($admin)->delete(route('admin.notifications.sounds.destroy', $sound))->assertRedirect();
    }
}
