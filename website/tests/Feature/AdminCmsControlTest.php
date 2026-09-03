<?php

namespace Tests\Feature;

use App\Models\AdminRecord;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminCmsControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_cms_modules_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        foreach (['home-page', 'sliders', 'home-sections', 'specializations', 'featured-jobs', 'featured-employers', 'testimonials', 'statistics', 'marquee-job-ticker', 'blog', 'pages', 'faq', 'footer', 'contact-information', 'social-links', 'app-links'] as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['cms', $view]))->assertOk()->assertSee('Live controls');
        }
    }

    public function test_admin_can_create_and_delete_cms_record(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.cms.records.store'), ['view' => 'testimonials', 'name' => 'Great service', 'description' => 'A customer testimonial', 'payload' => '{"quote":"Excellent"}', 'is_active' => 1])->assertRedirect();
        $record = AdminRecord::where('module', 'cms-testimonials')->where('name', 'Great service')->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.cms.records.destroy', $record))->assertRedirect();
        $this->assertDatabaseMissing('admin_records', ['id' => $record->id]);
    }

    public function test_admin_can_update_slider_with_background_image(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $slider = Slider::firstOrFail();
        $this->actingAs($admin)->put(route('admin.operations.update', ['sliders', $slider]), [
            'title' => $slider->title, 'subtitle' => $slider->subtitle, 'cta_text' => 'Explore jobs', 'cta_url' => '/jobs', 'sort_order' => 1, 'web_enabled' => 1, 'app_enabled' => 1, 'is_active' => 1, 'image' => UploadedFile::fake()->create('hero.jpg', 100, 'image/jpeg'),
        ])->assertRedirect();
        $this->assertNotNull(Slider::findOrFail($slider->id)->image_path);
    }
}
