<x-layouts.app title="Industry Specializations — Luckyboss Portal">
    {{-- Header Banner --}}
    <section class="bg-gradient-to-br from-navy via-primary-800 to-accent text-white py-14 lg:py-20 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-80 h-80 bg-secondary-500/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-accent/20 rounded-full blur-3xl"></div>

        <div class="container mx-auto px-6 relative z-10 text-center max-w-3xl">
            <span class="inline-block py-1 px-3 rounded-full bg-white/10 border border-white/20 text-xs font-semibold tracking-wider uppercase mb-4">
                Domain Expertise
            </span>
            <h1 class="text-3xl md:text-5xl font-serif !text-white font-normal mb-4 tracking-tight leading-tight">
                <span class="italic text-white">Our recruitment</span> 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-200 to-sky-200 font-sans font-extrabold not-italic">specializations.</span>
            </h1>
            <p class="text-blue-100/90 text-base md:text-lg leading-relaxed">
                We bridge talent and enterprise with dedicated recruitment pipelines across critical industries in Southeast Asia and India.
            </p>
        </div>
    </section>

    {{-- Specializations Grid --}}
    <section class="py-16 bg-surface">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-16">
                @foreach($categories as $category)
                    <div class="bg-white rounded-2xl p-6 border border-border hover:border-accent shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between">
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-blue-50 text-accent flex items-center justify-center mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <h3 class="text-lg font-heading font-bold text-navy mb-2">{{ $category->name }}</h3>
                            <p class="text-sm text-text-secondary leading-relaxed mb-4">
                                {{ $category->description ?: 'Dedicated candidate sourcing, technical screening, and compliance management for this sector.' }}
                            </p>
                        </div>
                        <div class="pt-4 border-t border-border">
                            <a href="{{ route('jobs.index', ['category' => $category->id]) }}" class="text-sm font-semibold text-accent hover:text-accent-dark inline-flex items-center gap-1 group">
                                View Open Positions <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Top Hiring Companies Section --}}
            @if(isset($companies) && $companies->count() > 0)
                <div class="bg-white rounded-3xl p-8 md:p-12 border border-border shadow-sm">
                    <div class="text-center max-w-2xl mx-auto mb-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-secondary-600 mb-2 block">Trusted Partners</span>
                        <h2 class="text-2xl md:text-3xl font-heading font-bold text-navy">Featured Employers In Our Network</h2>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
                        @foreach($companies as $company)
                            <div class="p-5 rounded-2xl bg-surface-sunken border border-border text-center hover:border-accent hover:bg-white transition-all shadow-xs">
                                <div class="w-12 h-12 mx-auto rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-accent mb-3">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                </div>
                                <h4 class="font-bold text-sm text-navy truncate">{{ $company->name }}</h4>
                                <span class="text-xs font-semibold text-secondary-600">{{ $company->country_code }} • Verified Employer</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>