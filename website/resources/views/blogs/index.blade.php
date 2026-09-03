<x-layouts.app title="Career Intelligence & Recruitment Blog | Luckyboss Portal">
    {{-- Top Banner --}}
    <section class="bg-gradient-to-b from-[#031533] via-[#041d45] to-[#031533] text-white py-16 lg:py-20 relative overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-secondary-500/10 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="container-app relative z-10 text-center max-w-4xl mx-auto">
            <span class="inline-flex items-center gap-2 py-1 px-4 rounded-full bg-white/10 border border-white/15 text-xs font-bold tracking-widest uppercase mb-4 text-secondary-300">
                ✦ CAREER INTELLIGENCE & INSIGHTS ✦
            </span>
            <h1 class="text-3xl sm:text-5xl font-serif font-normal tracking-tight mb-4 !text-white">
                <span class="italic font-serif block sm:inline">Navigating recruitment,</span> 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-200 to-sky-200 font-sans font-extrabold not-italic">building careers.</span>
            </h1>
            <p class="text-blue-100/80 text-base sm:text-lg max-w-2xl mx-auto font-normal">
                Actionable advice, industry market trends, resume masterclasses, and executive hiring strategies.
            </p>
        </div>
    </section>

    {{-- Main Blog Content --}}
    <section class="py-16 bg-[#f8fafc] flex-1">
        <div class="container-app">
            @php
                $editorialPhotos = [
                    'resume' => 'https://images.unsplash.com/photo-1586281380349-632531db7ed4?w=800&auto=format&fit=crop&q=80',
                    'logistics' => 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?w=800&auto=format&fit=crop&q=80',
                    'profile' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800&auto=format&fit=crop&q=80',
                    'pipeline' => 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=800&auto=format&fit=crop&q=80',
                    'interview' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=800&auto=format&fit=crop&q=80',
                    'warehouse' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=800&auto=format&fit=crop&q=80',
                ];
            @endphp

            {{-- Articles Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($blogs as $blog)
                    @php
                        $titleLower = strtolower($blog->title);
                        $matchedImg = 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=800&auto=format&fit=crop&q=80';
                        foreach($editorialPhotos as $key => $photo) {
                            if(str_contains($titleLower, $key) || str_contains(strtolower($blog->category ?? ''), $key)) {
                                $matchedImg = $photo;
                                break;
                            }
                        }
                    @endphp
                    <article class="bg-white rounded-3xl overflow-hidden border border-border shadow-xs hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between">
                        <div>
                            {{-- High-Resolution Editorial Cover --}}
                            <a href="{{ route('blogs.show', $blog->slug) }}" class="block aspect-[16/10] bg-surface-sunken relative overflow-hidden">
                                <img src="{{ $matchedImg }}" alt="{{ $blog->title }}" class="w-full h-full object-cover group-hover:scale-106 transition-transform duration-700 ease-out" loading="lazy">
                                <div class="absolute top-4 left-4">
                                    <span class="bg-white/95 backdrop-blur-md text-navy px-3.5 py-1 rounded-full text-xs font-bold shadow-xs">
                                        {{ $blog->category ?? 'Career Advice' }}
                                    </span>
                                </div>
                            </a>

                            {{-- Content Body --}}
                            <div class="p-6 sm:p-7">
                                <div class="text-xs text-text-muted mb-2.5 flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span>{{ $blog->published_at ? $blog->published_at->format('M d, Y') : 'Recent Post' }}</span>
                                    <span>•</span>
                                    <span>{{ $blog->author ?? 'Luckyboss Editorial' }}</span>
                                </div>

                                <h2 class="text-xl font-heading font-bold text-navy mb-2.5 group-hover:text-accent transition-colors leading-snug">
                                    <a href="{{ route('blogs.show', $blog->slug) }}">{{ $blog->title }}</a>
                                </h2>

                                <p class="text-text-secondary text-sm leading-relaxed line-clamp-3 mb-4">
                                    {{ $blog->short_description ?? Str::limit(strip_tags($blog->content), 130) }}
                                </p>
                            </div>
                        </div>

                        {{-- Footer Link --}}
                        <div class="px-6 sm:px-7 pb-6 pt-0 border-t border-border/50">
                            <a href="{{ route('blogs.show', $blog->slug) }}" class="inline-flex items-center text-accent font-bold text-xs hover:text-navy group/link pt-3 transition-colors">
                                <span>Read Full Article</span>
                                <svg class="w-3.5 h-3.5 ml-1.5 group-hover/link:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full py-16 text-center text-text-muted">
                        No articles published yet. Check back soon!
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.app>