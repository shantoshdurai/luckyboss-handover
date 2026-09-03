<x-admin-layout title="Recruitment Blog" heading="Recruitment Blog Management">
    <div class="space-y-6">
        {{-- Header Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-sm">
            <div>
                <h2 class="text-xl font-heading font-bold text-navy">Published Articles & Guides</h2>
                <p class="text-xs text-text-secondary mt-0.5">Manage public articles, resume guides, and employer recruitment advice.</p>
            </div>
            <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary btn-sm flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Write New Article</span>
            </a>
        </div>

        {{-- Blog Table Card --}}
        <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-surface-sunken border-b border-border text-xs uppercase font-bold text-text-muted tracking-wider">
                            <th class="py-3.5 px-6">Article Details</th>
                            <th class="py-3.5 px-6">Category</th>
                            <th class="py-3.5 px-6">Published Date</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($blogs as $blog)
                            <tr class="hover:bg-surface/60 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-12 rounded-xl bg-surface-sunken border border-border overflow-hidden shrink-0 shadow-2xs">
                                            @if($blog->image_path && $blog->image_path != 'images/lucky-boss-logo.png')
                                                <img src="{{ asset('storage/' . $blog->image_path) }}" alt="{{ $blog->title }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full bg-gradient-to-br from-navy to-primary-800 flex items-center justify-center text-white text-xs font-bold">
                                                    LB
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('blogs.show', $blog->slug) }}" target="_blank" class="font-bold text-navy hover:text-accent transition-colors block truncate max-w-md">
                                                {{ $blog->title }}
                                            </a>
                                            <span class="text-xs text-text-muted">By {{ $blog->author ?? 'Luckyboss Editorial' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-surface-sunken text-text-secondary border border-border">
                                        {{ $blog->category ?? 'Career Guide' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-xs text-text-muted font-medium">
                                    {{ $blog->published_at ? $blog->published_at->format('M d, Y') : 'Draft' }}
                                </td>
                                <td class="py-4 px-6">
                                    @if($blog->is_published)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Published
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-surface-sunken text-text-muted border border-border">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Draft
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <a href="{{ route('admin.blogs.edit', $blog) }}" class="text-xs font-bold text-accent hover:underline">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.blogs.destroy', $blog) }}" onsubmit="return confirm('Delete this article?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-danger hover:underline cursor-pointer">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-text-muted">
                                    No articles published yet. Click "Write New Article" to create your first post.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>