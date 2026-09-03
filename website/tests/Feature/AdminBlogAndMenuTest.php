<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminBlogAndMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_blog_with_image(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.blogs.store'), [
            'title' => 'Image Blog', 'category' => 'Guides', 'short_description' => 'A blog with an image.', 'content' => 'Content', 'author' => 'Lucky Boss Team', 'is_published' => 1, 'image' => UploadedFile::fake()->create('blog.jpg', 100, 'image/jpeg'),
        ])->assertRedirect(route('admin.blogs.index'));
        $this->assertNotNull(Blog::where('title', 'Image Blog')->firstOrFail()->image_path);
    }

    public function test_admin_layout_contains_menu_search(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Search menu')->assertSee('admin-menu-search');
    }
}
