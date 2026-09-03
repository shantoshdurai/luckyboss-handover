<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function index(): View
    {
        $this->authorizeAdmin();
        return view('admin.blogs.index', ['blogs' => Blog::latest()->paginate(20)]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();
        return view('admin.blogs.form', ['blog' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        Blog::create($this->data($request));
        return redirect()->route('admin.blogs.index')->with('success', 'Blog post published.');
    }

    public function edit(Blog $blog): View
    {
        $this->authorizeAdmin();
        return view('admin.blogs.form', compact('blog'));
    }

    public function update(Request $request, Blog $blog): RedirectResponse
    {
        $this->authorizeAdmin();
        $blog->update($this->data($request));
        return redirect()->route('admin.blogs.index')->with('success', 'Blog post updated.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $this->authorizeAdmin();
        $blog->delete();
        return redirect()->route('admin.blogs.index')->with('success', 'Blog post deleted.');
    }

    private function data(Request $request): array
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:180'], 'image' => ['nullable', 'image', 'max:4096'], 'category' => ['nullable', 'string', 'max:80'], 'short_description' => ['required', 'string', 'max:500'], 'content' => ['required', 'string'], 'author' => ['required', 'string', 'max:100'], 'published_at' => ['nullable', 'date']]);
        if ($request->hasFile('image')) {
            $file = $request->file('image'); $directory = public_path('uploads/blogs');
            if (! is_dir($directory)) mkdir($directory, 0755, true);
            $name = 'blog-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$file->extension(); $file->move($directory, $name); $data['image_path'] = 'uploads/blogs/'.$name;
        }
        unset($data['image']);
        $data['slug'] = Str::slug($data['title']);
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['is_published'] ? ($data['published_at'] ?? now()) : null;
        return $data;
    }
}