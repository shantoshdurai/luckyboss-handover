<x-layouts.app title="Explore Job Categories | Luckyboss Portal">
    {{-- Header Banner --}}
    <section class="bg-gradient-to-b from-[#031533] via-[#041d45] to-[#031533] text-white py-14 lg:py-18 relative overflow-hidden">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-secondary-500/10 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="container-app relative z-10 text-center max-w-3xl mx-auto">
            <span class="inline-flex items-center gap-2 py-1 px-4 rounded-full bg-white/10 border border-white/15 text-xs font-bold tracking-widest uppercase mb-4 text-secondary-300">
                ✦ INDUSTRY SECTORS ✦
            </span>
            <h1 class="text-3xl md:text-5xl font-serif !text-white font-normal mb-3 tracking-tight leading-tight">
                <span class="italic text-white">Explore jobs by</span> 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-200 to-sky-200 font-sans font-extrabold not-italic">category.</span>
            </h1>
            <p class="text-blue-100/90 text-base md:text-lg leading-relaxed">
                Discover verified career opportunities tailored to your specialized skills and experience across premier industries.
            </p>
        </div>
    </section>

    {{-- Categories Grid with Real Cover Photos --}}
    <section class="py-16 bg-[#f8fafc] flex-1">
        <div class="container-app">
            @php
                $categoryImages = [
                    'construction' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=600&q=80',
                    'manufacturing' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=600&q=80',
                    'warehouse' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
                    'healthcare' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=600&q=80',
                    'health' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=600&q=80',
                    'logistics' => 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=600&q=80',
                    'hospitality' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=600&q=80',
                    'domestic' => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=600&q=80',
                    'engineering' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80',
                    'engineer' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80',
                    'sales' => 'https://images.unsplash.com/photo-1556740738-b6a63e27c4df?auto=format&fit=crop&w=600&q=80',
                    'admin' => 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=600&q=80',
                    'security' => 'https://images.unsplash.com/photo-1557597774-9d273605dfa9?auto=format&fit=crop&w=600&q=80',
                ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @forelse($categories as $category)
                    @php 
                        $slug = Str::slug($category->name);
                        $matchedImage = 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80';
                        foreach($categoryImages as $key => $img) {
                            if(Str::contains($slug, $key) || Str::contains(strtolower($category->name), $key)) {
                                $matchedImage = $img;
                                break;
                            }
                        }
                        $jobCount = $category->jobs_count ?? ($category->jobs ? $category->jobs->count() : 0);
                    @endphp
                    <a href="{{ route('jobs.index', ['category' => $category->id]) }}" 
                       class="group bg-white rounded-3xl overflow-hidden border border-border hover:border-accent shadow-xs hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between">
                        <div>
                            {{-- High-Resolution Category Cover Photo --}}
                            <div class="h-44 w-full relative overflow-hidden bg-surface-sunken">
                                <img src="{{ $matchedImage }}" alt="{{ $category->name }}" class="w-full h-full object-cover group-hover:scale-108 transition-transform duration-700 ease-out" loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent"></div>
                                <div class="absolute bottom-3 left-4 right-4 flex items-center justify-between text-white">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/20 backdrop-blur-md border border-white/30 text-white">
                                        {{ number_format($jobCount) }} Jobs Available
                                    </span>
                                </div>
                            </div>

                            {{-- Category Title & Description --}}
                            <div class="p-6">
                                <h3 class="font-heading font-bold text-xl text-navy mb-1.5 group-hover:text-accent transition-colors">
                                    {{ $category->name }}
                                </h3>
                                <p class="text-xs text-text-muted leading-relaxed line-clamp-2">
                                    {{ $category->description ?? 'Explore top verified roles and certified companies hiring in this sector.' }}
                                </p>
                            </div>
                        </div>

                        {{-- Card Footer Link --}}
                        <div class="px-6 pb-5 pt-0 flex items-center justify-between text-xs font-bold text-accent group-hover:text-navy transition-colors">
                            <span>Browse Opportunities</span>
                            <div class="w-7 h-7 rounded-full bg-blue-50 group-hover:bg-accent group-hover:text-white flex items-center justify-center transition-colors">
                                <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full py-16 text-center text-text-muted">
                        No categories found.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Bottom CTA --}}
    <section class="py-16 bg-white border-t border-border">
        <div class="container mx-auto px-6 text-center max-w-2xl">
            <h2 class="text-2xl md:text-3xl font-heading font-bold text-navy mb-4">Don't see your specific specialization?</h2>
            <p class="text-text-secondary mb-8">Search all active jobs across Singapore, Malaysia, and India, or register your profile to get personalized alerts.</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('jobs.index') }}" class="btn btn-primary btn-md shadow-md">Search All Jobs</a>
                <a href="{{ route('register.seeker') }}" class="btn btn-outline btn-md">Create Free Profile</a>
            </div>
        </div>
    </section>
</x-layouts.app>