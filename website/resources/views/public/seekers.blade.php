<x-layouts.app title="For Job Seekers — Luckyboss Global Recruitment">
    {{-- ═══════════════════════════════════════════════════════════
         1. HERO SECTION: LUXURY MIDNIGHT NAVY SKYLINE
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="relative bg-[#031533] text-white py-20 lg:py-28 overflow-hidden">
        {{-- High-Resolution Metropolitan Skyline Background --}}
        <div class="absolute inset-0 z-0">
            <img 
                src="https://images.unsplash.com/photo-1508964942454-1a56651d54ac?w=1920&auto=format&fit=crop&q=85" 
                alt="Singapore Metropolitan Business District" 
                class="w-full h-full object-cover object-center opacity-25 filter brightness-90 contrast-110"
            >
            <div class="absolute inset-0 bg-gradient-to-b from-[#031533]/85 via-[#031533]/90 to-[#031533]"></div>
        </div>

        <div class="container mx-auto px-6 relative z-10 text-center max-w-4xl">
            <div class="inline-flex items-center gap-2 py-1 px-4 rounded-full bg-white/10 border border-white/20 text-xs font-bold tracking-widest uppercase mb-5 text-secondary-300">
                <span class="w-2 h-2 rounded-full bg-secondary-400 animate-pulse"></span>
                <span>Verified Direct Placements</span>
            </div>

            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-heading font-extrabold text-white mb-6 tracking-tight leading-tight">
                Launch Your Career with <br class="hidden sm:inline">
                <span class="text-secondary-400">Direct Corporate Employers.</span>
            </h1>

            <p class="text-slate-200 text-base sm:text-lg lg:text-xl leading-relaxed mb-10 max-w-2xl mx-auto font-sans">
                Connect directly with verified corporate enterprises across Singapore, Malaysia, and India. No middleman agency fees, no spam, and transparent ATS interview tracking.
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('register.seeker') }}" class="btn btn-secondary btn-lg px-8 py-3.5 shadow-xl hover:shadow-2xl font-bold text-sm font-sans">
                    Create Free Profile &rarr;
                </a>
                <a href="{{ route('jobs.index') }}" class="btn btn-outline btn-lg px-8 py-3.5 border-white/30 text-white hover:bg-white/15 font-bold text-sm font-sans">
                    Explore 5,000+ Jobs
                </a>
            </div>

            {{-- 3 Candidate Trust Pillars --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-16 pt-10 border-t border-white/10 text-left">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">100% Free For Candidates</h4>
                        <p class="text-xs text-slate-300">Zero recruitment or placement charges.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6h1.5m-1.5 3h1.5m-1.5 3h1.5M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Verified Employers</h4>
                        <p class="text-xs text-slate-300">Vetted MNCs and enterprise organizations.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-secondary-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-white">Direct ATS Speed</h4>
                        <p class="text-xs text-slate-300">Fast application review and status tracking.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         2. 4-STEP RECRUITMENT JOURNEY
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="py-20 bg-white border-b border-border">
        <div class="container mx-auto px-6 max-w-6xl">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-widest text-secondary-600 mb-2 block">Candidate Roadmap</span>
                <h2 class="text-3xl sm:text-4xl font-heading font-extrabold text-navy mb-3">How You Get Hired on Luckyboss</h2>
                <p class="text-text-secondary text-sm sm:text-base">A streamlined 4-step hiring pipeline designed to put your profile in front of hiring managers.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                {{-- Step 1 --}}
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 relative group hover:border-slate-300 transition-all">
                    <div class="w-8 h-8 rounded-full bg-navy text-white font-bold text-xs flex items-center justify-center mb-4">1</div>
                    <h3 class="text-base font-bold text-navy mb-2">Build Profile & Skills</h3>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        Upload your resume or use our autocomplete skill directory to build a comprehensive professional profile.
                    </p>
                </div>

                {{-- Step 2 --}}
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 relative group hover:border-slate-300 transition-all">
                    <div class="w-8 h-8 rounded-full bg-navy text-white font-bold text-xs flex items-center justify-center mb-4">2</div>
                    <h3 class="text-base font-bold text-navy mb-2">Instant Verification</h3>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        Get verified candidate badging to stand out in corporate recruiter search queries and candidate pools.
                    </p>
                </div>

                {{-- Step 3 --}}
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 relative group hover:border-slate-300 transition-all">
                    <div class="w-8 h-8 rounded-full bg-navy text-white font-bold text-xs flex items-center justify-center mb-4">3</div>
                    <h3 class="text-base font-bold text-navy mb-2">1-Click Apply</h3>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        Apply to verified openings with one click. Your full credentials route directly into the employer's ATS dashboard.
                    </p>
                </div>

                {{-- Step 4 --}}
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 relative group hover:border-slate-300 transition-all">
                    <div class="w-8 h-8 rounded-full bg-secondary-500 text-white font-bold text-xs flex items-center justify-center mb-4">4</div>
                    <h3 class="text-base font-bold text-navy mb-2">Interview & Offer</h3>
                    <p class="text-xs text-text-secondary leading-relaxed">
                        Track interview schedules, join technical meetings, and receive official digital offer placement letters.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         3. SALARY BENCHMARKS: SINGAPORE & INDIA
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="py-20 bg-[#f8fafc] border-b border-border">
        <div class="container mx-auto px-6 max-w-6xl">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-xs font-bold uppercase tracking-widest text-secondary-600 mb-2 block">Compensation Transparency</span>
                <h2 class="text-3xl sm:text-4xl font-heading font-extrabold text-navy mb-3">Live Sector Salary Ranges</h2>
                <p class="text-text-secondary text-sm sm:text-base">Real compensation benchmarks from verified active postings across Singapore and India.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Singapore Benchmark Card --}}
                <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🇸🇬</span>
                            <h3 class="font-bold text-navy text-base">Singapore Key Sectors</h3>
                        </div>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">SGD / Month</span>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="font-bold text-navy">Warehouse Supervisor & Logistics</span>
                            <span class="font-extrabold text-secondary-600">SGD 3,000 – 4,500</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="font-bold text-navy">Civil & Construction Site Supervisor</span>
                            <span class="font-extrabold text-secondary-600">SGD 4,200 – 5,800</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="font-bold text-navy">Healthcare & Clinical Assistant</span>
                            <span class="font-extrabold text-secondary-600">SGD 2,400 – 3,400</span>
                        </div>
                    </div>
                </div>

                {{-- India Benchmark Card --}}
                <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🇮🇳</span>
                            <h3 class="font-bold text-navy text-base">India Key Sectors</h3>
                        </div>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">INR / Month</span>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="font-bold text-navy">Cloud Platform & DevOps Engineer</span>
                            <span class="font-extrabold text-secondary-600">₹ 85,000 – 1,40,000</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="font-bold text-navy">Supply Chain & Logistics Operations</span>
                            <span class="font-extrabold text-secondary-600">₹ 60,000 – 95,000</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="font-bold text-navy">Civil Infrastructure Project Engineer</span>
                            <span class="font-extrabold text-secondary-600">₹ 70,000 – 1,15,000</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         4. CALL TO ACTION BANNER
    ═══════════════════════════════════════════════════════════ --}}
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6 max-w-5xl">
            <div class="bg-[#031533] rounded-3xl p-8 sm:p-14 text-center text-white relative overflow-hidden shadow-2xl">
                <h2 class="text-3xl sm:text-4xl font-heading font-extrabold mb-4">
                    Ready to Connect with Top Employers?
                </h2>
                <p class="text-slate-200 text-sm sm:text-base max-w-xl mx-auto mb-8 font-sans">
                    Create your profile in 2 minutes, upload your resume, and let our verified matching system connect you with high-paying opportunities.
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('register.seeker') }}" class="btn btn-secondary btn-lg px-8 font-bold text-sm shadow-lg">
                        Create Candidate Account
                    </a>
                    <a href="{{ route('jobs.index') }}" class="btn btn-outline btn-lg px-8 border-white/30 text-white hover:bg-white/10 font-bold text-sm">
                        Browse Open Jobs
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>