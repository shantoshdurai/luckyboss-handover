<x-layouts.app title="Modern Job Opportunities List — Luckyboss">
    {{-- Header Hero Banner --}}
    <section class="bg-gradient-to-br from-navy via-[#062456] to-[#031533] text-white py-12 lg:py-16 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-secondary-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="container mx-auto px-6 relative z-10">
            <div class="max-w-3xl mb-8">
                <span class="inline-block py-1 px-3.5 rounded-full bg-white/10 border border-white/20 text-xs font-semibold tracking-wider uppercase mb-3 text-emerald-300">
                    Verified Opportunities
                </span>
                <h1 class="text-3xl md:text-5xl font-heading font-extrabold text-white mb-3 leading-tight tracking-tight">
                    Modern Job Opportunities List
                </h1>
                <p class="text-slate-300 text-sm md:text-base max-w-2xl">
                    Discover verified vacancies across Singapore, Malaysia, and India with real-time application tracking and AI match scoring.
                </p>
            </div>

            {{-- Fast Search Bar --}}
            <form method="GET" action="{{ route('jobs.index') }}" class="bg-white/95 backdrop-blur-md p-3 rounded-2xl shadow-2xl border border-white/20">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                    {{-- Keyword Search with Autocomplete --}}
                    <div class="md:col-span-5 relative" x-data="{ term: '{{ request('keyword') }}', suggestions: [], show: false }">
                        <div class="flex items-center pl-3.5 bg-slate-50 rounded-xl h-12 border border-slate-200">
                            <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input 
                                type="text" 
                                name="keyword" 
                                placeholder="Search job title, skills, or keywords..." 
                                class="w-full bg-transparent border-0 focus:ring-0 text-navy px-3 text-sm h-full font-medium"
                                x-model="term"
                                @input.debounce.300ms="
                                    if(term.length > 1) {
                                        fetch('{{ route('jobs.suggestions') }}?field=title&q=' + encodeURIComponent(term))
                                            .then(res => res.json())
                                            .then(data => { suggestions = data; show = true; });
                                    } else {
                                        show = false;
                                    }
                                "
                                @click.away="show = false"
                                autocomplete="off"
                            >
                        </div>
                        <div x-show="show && suggestions.length > 0" x-cloak class="absolute top-full left-0 right-0 bg-white border border-border shadow-2xl rounded-xl mt-1 z-50 overflow-hidden">
                            <template x-for="s in suggestions" :key="s">
                                <div @click="term = s; show = false; $el.closest('form').submit();" class="px-4 py-2.5 hover:bg-slate-50 cursor-pointer text-navy text-xs font-semibold transition-colors flex items-center justify-between">
                                    <span x-text="s"></span>
                                    <span class="text-[10px] text-slate-400 font-normal">Search &rarr;</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Category --}}
                    <div class="md:col-span-3">
                        <select name="category" class="w-full bg-slate-50 border border-slate-200 text-navy text-xs font-semibold rounded-xl h-12 px-3.5 focus:ring-2 focus:ring-accent">
                            <option value="">All Job Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Country --}}
                    <div class="md:col-span-2">
                        <select name="country" class="w-full bg-slate-50 border border-slate-200 text-navy text-xs font-semibold rounded-xl h-12 px-3.5 focus:ring-2 focus:ring-accent">
                            <option value="">All Countries</option>
                            @foreach($countries as $cnt)
                                <option value="{{ $cnt->code }}" @selected(request('country') === $cnt->code)>{{ $cnt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Submit Button --}}
                    <div class="md:col-span-2">
                        <button type="submit" class="w-full h-12 btn btn-primary font-bold rounded-xl flex items-center justify-center gap-2 shadow-md cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <span>Search Jobs</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- Main 2-Column Content Area (Filter Sidebar + Horizontal Job Cards) --}}
    <section class="py-12 bg-slate-50">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                {{-- Left Column: Interactive Filter Sidebar (Stitch Layout) --}}
                <aside class="lg:col-span-4 xl:col-span-3 space-y-6">
                    <form method="GET" action="{{ route('jobs.index') }}" class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-6">
                        @if(request('keyword')) <input type="hidden" name="keyword" value="{{ request('keyword') }}"> @endif
                        @if(request('category')) <input type="hidden" name="category" value="{{ request('category') }}"> @endif
                        @if(request('country')) <input type="hidden" name="country" value="{{ request('country') }}"> @endif

                        <div class="flex items-center justify-between border-b border-border pb-3">
                            <h3 class="text-base font-bold text-navy">Filter Jobs</h3>
                            @if(request()->hasAny(['keyword', 'category', 'country', 'location', 'work_mode', 'job_type', 'min_salary', 'max_salary', 'experience']))
                                <a href="{{ route('jobs.index') }}" class="text-xs font-bold text-rose-600 hover:underline">
                                    Clear All
                                </a>
                            @endif
                        </div>

                        {{-- 1. Job Type Checkboxes --}}
                        <div class="space-y-2.5">
                            <label class="block text-xs font-bold text-navy uppercase tracking-wider">Job Type</label>
                            <div class="space-y-2">
                                @foreach(['Full Time' => 'full-time', 'Part Time' => 'part-time', 'Contract' => 'contract', 'Internship' => 'internship'] as $label => $val)
                                    <label class="flex items-center gap-2.5 text-xs text-text-secondary font-medium cursor-pointer hover:text-navy">
                                        <input type="radio" 
                                               name="job_type" 
                                               value="{{ $val }}" 
                                               @checked(request('job_type') === $val) 
                                               onchange="this.form.submit()" 
                                               class="rounded border-slate-300 text-accent focus:ring-accent">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- 2. Work Mode --}}
                        <div class="space-y-2.5 pt-4 border-t border-border">
                            <label class="block text-xs font-bold text-navy uppercase tracking-wider">Work Mode</label>
                            <div class="space-y-2">
                                @foreach(['All Modes' => '', 'On-Site' => 'on-site', 'Hybrid' => 'hybrid', 'Remote' => 'remote'] as $label => $val)
                                    <label class="flex items-center gap-2.5 text-xs text-text-secondary font-medium cursor-pointer hover:text-navy">
                                        <input type="radio" 
                                               name="work_mode" 
                                               value="{{ $val }}" 
                                               @checked(request('work_mode') === $val) 
                                               onchange="this.form.submit()" 
                                               class="rounded border-slate-300 text-accent focus:ring-accent">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- 3. Salary Range Inputs --}}
                        <div class="space-y-2.5 pt-4 border-t border-border">
                            <label class="block text-xs font-bold text-navy uppercase tracking-wider">Monthly Salary ($)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <input type="number" 
                                           name="min_salary" 
                                           value="{{ request('min_salary') }}" 
                                           placeholder="Min $" 
                                           class="input w-full text-xs py-2">
                                </div>
                                <div>
                                    <input type="number" 
                                           name="max_salary" 
                                           value="{{ request('max_salary') }}" 
                                           placeholder="Max $" 
                                           class="input w-full text-xs py-2">
                                </div>
                            </div>
                        </div>

                        {{-- 4. Experience Level --}}
                        <div class="space-y-2.5 pt-4 border-t border-border">
                            <label class="block text-xs font-bold text-navy uppercase tracking-wider">Experience Level</label>
                            <div class="space-y-2">
                                @foreach(['All Experience' => '', 'Entry Level (0-2 yrs)' => 'entry', 'Mid Level (3-5 yrs)' => 'mid', 'Senior Level (6+ yrs)' => 'senior'] as $label => $val)
                                    <label class="flex items-center gap-2.5 text-xs text-text-secondary font-medium cursor-pointer hover:text-navy">
                                        <input type="radio" 
                                               name="experience" 
                                               value="{{ $val }}" 
                                               @checked(request('experience') === $val) 
                                               onchange="this.form.submit()" 
                                               class="rounded border-slate-300 text-accent focus:ring-accent">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-full justify-center text-xs font-bold shadow-xs">
                            Apply Filters
                        </button>
                    </form>
                </aside>

                {{-- Right Column: Horizontal Sleek Job Opportunities List (Stitch Card Layout) --}}
                <main class="lg:col-span-8 xl:col-span-9 space-y-4">
                    {{-- Heading Bar --}}
                    <div class="bg-white rounded-2xl border border-border p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-heading font-extrabold text-navy">
                                Modern Job Opportunities List
                            </h2>
                            <p class="text-xs text-text-muted mt-0.5">
                                Showing <span class="font-bold text-navy">{{ $jobs->total() }}</span> published positions
                            </p>
                        </div>

                        <div class="text-xs text-slate-500 font-medium">
                            Sorted by: <span class="font-bold text-navy">Latest Active Openings</span>
                        </div>
                    </div>

                    {{-- Horizontal Sleek Cards Stack --}}
                    <div class="space-y-4">
                        @forelse($jobs as $job)
                            @php 
                                $companyName = $job->company->name ?? 'Verified Regional Partner'; 
                                $isSaved = in_array($job->id, $savedJobIds ?? [], true);
                                $hasApplied = in_array($job->id, $appliedJobIds ?? [], true);
                            @endphp

                            <article class="bg-white rounded-2xl border border-border hover:border-accent/50 p-6 shadow-xs hover:shadow-md transition-all group">
                                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                                    {{-- Job Details & Badges --}}
                                    <div class="space-y-2.5 flex-1 min-w-0">
                                        {{-- Top Row: Job Title --}}
                                        <div class="flex items-center gap-3">
                                            <h3 class="text-lg font-bold text-navy group-hover:text-accent transition-colors">
                                                <a href="{{ route('jobs.show', $job) }}">{{ $job->title }}</a>
                                            </h3>
                                        </div>

                                        {{-- Second Row: Employer Name & Location --}}
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-text-secondary">
                                            <span class="font-bold text-navy flex items-center gap-1">
                                                <span>{{ $companyName }}</span>
                                                <svg class="w-4 h-4 text-blue-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            </span>
                                            <span class="text-slate-300">&bull;</span>
                                            <span>{{ $job->location }}</span>
                                            @if($job->salary_visible && $job->salary_min)
                                                <span class="text-slate-300">&bull;</span>
                                                <span class="font-bold text-navy">
                                                    {{ $job->currency_code }} {{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }} / mo
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Third Row: Stitch-Style Badge Pills --}}
                                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ $job->job_type ? str($job->job_type)->headline() : 'Full Time' }}
                                            </span>
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                                {{ str($job->work_mode)->headline() }}
                                            </span>
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ $job->experience_min }}-{{ $job->experience_max }} yrs exp
                                            </span>
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Verified Role
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Right Side Actions: Saved Job & Apply Status Button --}}
                                    <div class="flex md:flex-col items-center md:items-end justify-between md:justify-start gap-4 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-border">
                                        {{-- Save Job Toggle --}}
                                        @auth
                                            <form method="POST" action="{{ route('seeker.jobs.save', $job) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-navy transition-colors cursor-pointer" title="{{ $isSaved ? 'Remove from Saved' : 'Save Job' }}">
                                                    <svg class="w-4 h-4 {{ $isSaved ? 'text-rose-500 fill-rose-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                                    <span>{{ $isSaved ? 'Saved' : 'Save Job' }}</span>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-navy transition-colors">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                                <span>Save Job</span>
                                            </a>
                                        @endauth

                                        {{-- Apply Status Button --}}
                                        @if($hasApplied)
                                            <a href="{{ route('seeker.dashboard', ['tab' => 'applications']) }}" class="inline-flex items-center gap-1.5 py-2.5 px-5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-300 text-xs font-bold shadow-xs hover:bg-emerald-100 transition-colors">
                                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                <span>Applied &bull; Track Status</span>
                                            </a>
                                        @else
                                            <a href="{{ route('jobs.show', $job) }}" class="btn btn-primary py-2.5 px-6 text-xs font-bold shadow-md">
                                                View & Apply
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="bg-white rounded-2xl border border-border p-12 text-center shadow-xs">
                                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <h4 class="text-base font-bold text-navy">No matching job opportunities found</h4>
                                <p class="text-xs text-text-muted mt-1">Try adjusting your filters or search keywords.</p>
                                <a href="{{ route('jobs.index') }}" class="btn btn-outline btn-sm mt-4 text-xs font-bold inline-flex">
                                    Reset Filters
                                </a>
                            </div>
                        @endforelse
                    </div>

                    {{-- Pagination --}}
                    @if($jobs->hasPages())
                        <div class="mt-8 pt-4 bg-white p-4 rounded-2xl border border-border shadow-xs">
                            {{ $jobs->links() }}
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </section>
</x-layouts.app>
