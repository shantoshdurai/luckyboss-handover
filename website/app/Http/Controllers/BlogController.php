<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('blogs.index', ['blogs' => Blog::where('is_published', true)->latest('published_at')->paginate(12)]);
    }

    public function show(Blog $blog): View
    {
        abort_unless($blog->is_published, 404);

        return view('blogs.show', compact('blog'));
    }
}