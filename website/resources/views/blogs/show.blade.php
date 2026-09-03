<x-layouts.app title="{{ $blog->title }} | Luckyboss Career Intelligence">
    {{-- Breadcrumbs & Header --}}
    <section class="bg-gradient-to-b from-[#031533] to-[#041d45] text-white pt-10 pb-16 relative overflow-hidden">
        <div class="container-app max-w-4xl mx-auto">
            {{-- Breadcrumbs --}}
            <nav class="flex items-center gap-2 text-xs text-blue-200/70 mb-6">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <span>/</span>
                <a href="{{ route('blogs.index') }}" class="hover:text-white transition-colors">Career Intelligence</a>
                <span>/</span>
                <span class="text-white truncate">{{ $blog->title }}</span>
            </nav>

            {{-- Category Pill --}}
            <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-secondary-500/20 text-secondary-300 border border-secondary-400/30 mb-4">
                {{ $blog->category ?? 'Career Intelligence' }}
            </span>

            {{-- Title --}}
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-serif !text-white font-normal tracking-tight leading-[1.2] mb-6">
                {{ $blog->title }}
            </h1>

            {{-- Meta Row --}}
            <div class="flex items-center gap-4 text-xs text-blue-200/80">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 font-bold text-white">
                        <svg class="w-4 h-4 text-secondary-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <span>{{ $blog->author ?? 'Luckyboss Editorial' }}</span>
                    </span>
                </div>
                <span>•</span>
                <span>{{ $blog->published_at ? $blog->published_at->format('M d, Y') : 'Published Recently' }}</span>
            </div>
        </div>
    </section>

    {{-- Article Body --}}
    <article class="py-16 bg-white flex-1">
        <div class="container-app max-w-4xl mx-auto">
            @php
                $editorialPhotos = [
                    'resume' => 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?w=1200&auto=format&fit=crop&q=80',
                    'logistics' => 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?w=1200&auto=format&fit=crop&q=80',
                    'profile' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&auto=format&fit=crop&q=80',
                    'pipeline' => 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=1200&auto=format&fit=crop&q=80',
                    'interview' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=1200&auto=format&fit=crop&q=80',
                    'warehouse' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=1200&auto=format&fit=crop&q=80',
                ];
                $titleLower = strtolower($blog->title);
                $coverImg = 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=1200&auto=format&fit=crop&q=80';
                foreach($editorialPhotos as $key => $photo) {
                    if(str_contains($titleLower, $key) || str_contains(strtolower($blog->category ?? ''), $key)) {
                        $coverImg = $photo;
                        break;
                    }
                }
            @endphp

            {{-- High-Resolution Editorial Hero Cover Photo --}}
            <div class="rounded-3xl overflow-hidden shadow-xl mb-12 border border-border bg-surface-sunken aspect-[21/9]">
                <img src="{{ $coverImg }}" alt="{{ $blog->title }}" class="w-full h-full object-cover">
            </div>

            {{-- Text Content --}}
            <div class="prose prose-lg max-w-none text-text-primary leading-relaxed space-y-6 text-base sm:text-lg">
                <div class="p-6 bg-surface-sunken rounded-2xl border-l-4 border-accent text-text-secondary font-medium italic">
                    {{ $blog->short_description ?? 'Strategic advice and guidance for job seekers and hiring managers across Singapore and the region.' }}
                </div>

                <div class="whitespace-pre-line text-text-secondary">
                    {{ $blog->content }}
                </div>
            </div>

            {{-- Back CTA --}}
            <div class="mt-14 pt-8 border-t border-border flex items-center justify-between">
                <a href="{{ route('blogs.index') }}" class="btn btn-outline btn-sm">
                    ← Back to All Articles
                </a>
                <a href="{{ route('jobs.index') }}" class="btn btn-primary btn-sm">
                    Explore Open Vacancies →
                </a>
            </div>
        </div>
    </article>
</x-layouts.app>