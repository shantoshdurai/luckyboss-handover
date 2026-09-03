@props(['title' => 'Admin', 'heading' => null])
<?php $branding = app(\App\Services\SiteSettingsService::class)->branding(); ?>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — Luckyboss Employment Agency Admin</title>
    <link rel="icon" type="image/png" href="{{ asset($branding['favicon_url'] ?? 'images/lucky-boss-logo.png') }}">
    <script type="application/ld+json">@json(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $branding['site_name'] ?? 'Luckyboss Employment Agency Pte. Ltd'])</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f8fafc] text-text-primary antialiased" x-data="{ 
    sidebarOpen: true, 
    mobileSidebarOpen: false,
    openDropdowns: {
        employers: {{ request()->routeIs('admin.companies.*', 'admin.employer-*') ? 'true' : 'false' }},
        candidates: {{ request()->routeIs('admin.candidates.*', 'admin.candidate-*') ? 'true' : 'false' }},
        jobs: {{ request()->routeIs('admin.jobs.*') ? 'true' : 'false' }},
        recruitment: {{ request()->routeIs('admin.recruitment.*', 'admin.interviews.*') ? 'true' : 'false' }},
        commercial: {{ request()->routeIs('admin.subscriptions.*', 'admin.payments.*', 'admin.invoices.*', 'admin.operations.*') ? 'true' : 'false' }},
        ai: {{ request()->routeIs('admin.ai-api.*') ? 'true' : 'false' }},
        masters: {{ request()->routeIs('admin.records.*', 'admin.masters.*', 'admin.external-data.*') ? 'true' : 'false' }},
        cms: {{ request()->routeIs('admin.cms.*', 'admin.blogs.*') ? 'true' : 'false' }},
        comms: {{ request()->routeIs('admin.communication.*', 'admin.notifications.*', 'admin.support-center.*', 'admin.support*') ? 'true' : 'false' }},
        reports: {{ request()->routeIs('admin.reports.*', 'admin.control-center.*') ? 'true' : 'false' }},
        settings: {{ request()->routeIs('admin.site-settings.*') ? 'true' : 'false' }}
    },
    toggleDropdown(name) {
        this.openDropdowns[name] = !this.openDropdowns[name];
    }
}">
    <div class="flex min-h-screen">
        {{-- Desktop Sidebar (Clean White with High-Contrast Slate/Navy Styling) --}}
        <aside
            :class="sidebarOpen ? 'w-64' : 'w-20'"
            class="hidden lg:flex flex-col fixed inset-y-0 left-0 z-30 bg-white border-r border-border overflow-hidden shadow-xs transition-all duration-200"
        >
            {{-- Admin Sidebar Logo Header --}}
            <div class="flex items-center justify-center h-24 border-b border-slate-200 bg-gradient-to-br from-slate-50 to-white px-3 py-3">
                <a href="{{ route('admin.control-center.index', ['section' => 'overview', 'view' => 'dashboard']) }}" class="flex items-center group focus:outline-none w-full">
                    <img 
                        :src="sidebarOpen ? '{{ asset($branding['logo_url'] ?? 'images/luckyboss-logo.svg') }}' : '{{ asset($branding['logo_url'] ?? 'images/luckyboss-logo.svg') }}'"
                        alt="Luckyboss Admin"
                        class="h-10 lg:h-14 w-auto max-w-full object-contain transition-all duration-300 group-hover:scale-105"
                        :class="sidebarOpen ? 'px-1' : 'px-1'"
                    >
                </a>
            </div>

            {{-- Navigation Items --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 scrollbar-thin">
                
                {{-- 1. Dashboard --}}
                <a href="{{ route('admin.dashboard') }}"
                   @class([
                       'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group',
                       'bg-navy text-white font-bold shadow-xs' => request()->routeIs('admin.dashboard'),
                       'text-slate-700 hover:text-navy hover:bg-slate-100' => !request()->routeIs('admin.dashboard'),
                   ])
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-slate-500 group-hover:text-navy' }}" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                        <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                        <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                        <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                    </svg>
                    <span x-show="sidebarOpen" class="whitespace-nowrap">Dashboard</span>
                </a>

                {{-- 2. Employers --}}
                <div>
                    <button @click="toggleDropdown('employers')" type="button" 
                            @class([
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group cursor-pointer',
                                'bg-slate-100 text-navy font-bold' => request()->routeIs('admin.companies.*', 'admin.employer-*'),
                                'text-slate-700 hover:text-navy hover:bg-slate-100' => !request()->routeIs('admin.companies.*', 'admin.employer-*'),
                            ])>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 text-slate-500 group-hover:text-navy" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">Employers</span>
                        </div>
                        <svg x-show="sidebarOpen" :class="openDropdowns.employers ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openDropdowns.employers && sidebarOpen" x-cloak class="mt-1 pl-9 pr-2 space-y-1 text-xs border-l-2 border-slate-200 ml-5">
                        <a href="{{ route('admin.companies.index') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">All Companies</a>
                        <a href="{{ route('admin.companies.index', ['status' => 'pending']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Verification Queue</a>
                        <a href="{{ route('admin.employer-users.index') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Employer Users</a>
                        <a href="{{ route('admin.employer-documents.index') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Documents</a>
                        <a href="{{ route('admin.employer-activity.index') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Activity Logs</a>
                        <a href="{{ route('admin.employer-notes.index') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Internal Notes</a>
                    </div>
                </div>

                {{-- 3. Candidates --}}
                <div>
                    <button @click="toggleDropdown('candidates')" type="button" 
                            @class([
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group cursor-pointer',
                                'bg-slate-100 text-navy font-bold' => request()->routeIs('admin.candidates.*', 'admin.candidate-*'),
                                'text-slate-700 hover:text-navy hover:bg-slate-100' => !request()->routeIs('admin.candidates.*', 'admin.candidate-*'),
                            ])>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 text-slate-500 group-hover:text-navy" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">Candidates</span>
                        </div>
                        <svg x-show="sidebarOpen" :class="openDropdowns.candidates ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openDropdowns.candidates && sidebarOpen" x-cloak class="mt-1 pl-9 pr-2 space-y-1 text-xs border-l-2 border-slate-200 ml-5">
                        <a href="{{ route('admin.candidates.index', ['view' => 'all-job-seekers']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">All Job Seekers</a>
                        <a href="{{ route('admin.candidates.index', ['view' => 'verified-candidates']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Verified Profiles</a>
                        <a href="{{ route('admin.candidates.index', ['view' => 'incomplete-profiles']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Incomplete Profiles</a>
                        <a href="{{ route('admin.candidates.index', ['view' => 'candidate-resumes']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Resumes & Parsing</a>
                        <a href="{{ route('admin.candidates.index', ['view' => 'candidate-skills']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Skills Directory</a>
                        <a href="{{ route('admin.candidates.index', ['view' => 'candidate-applications']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Applications</a>
                    </div>
                </div>

                {{-- 4. Job Listings --}}
                <div>
                    <button @click="toggleDropdown('jobs')" type="button" 
                            @class([
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group cursor-pointer',
                                'bg-slate-100 text-navy font-bold' => request()->routeIs('admin.jobs.*'),
                                'text-slate-700 hover:text-navy hover:bg-slate-100' => !request()->routeIs('admin.jobs.*'),
                            ])>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 text-slate-500 group-hover:text-navy" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">Job Listings</span>
                        </div>
                        <svg x-show="sidebarOpen" :class="openDropdowns.jobs ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openDropdowns.jobs && sidebarOpen" x-cloak class="mt-1 pl-9 pr-2 space-y-1 text-xs border-l-2 border-slate-200 ml-5">
                        <a href="{{ route('admin.jobs.index', ['view' => 'all-jobs']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">All Jobs</a>
                        <a href="{{ route('admin.jobs.index', ['view' => 'pending-approval']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Approval Queue</a>
                        <a href="{{ route('admin.jobs.index', ['view' => 'featured-jobs']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Featured Roles</a>
                        <a href="{{ route('admin.jobs.create') }}" class="block py-1.5 px-2 rounded-lg text-secondary-600 hover:text-navy font-bold">+ Post New Job</a>
                    </div>
                </div>

                {{-- 5. ATS Pipeline --}}
                <div>
                    <button @click="toggleDropdown('recruitment')" type="button" 
                            @class([
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group cursor-pointer',
                                'bg-slate-100 text-navy font-bold' => request()->routeIs('admin.recruitment.*', 'admin.interviews.*'),
                                'text-slate-700 hover:text-navy hover:bg-slate-100' => !request()->routeIs('admin.recruitment.*', 'admin.interviews.*'),
                            ])>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 text-slate-500 group-hover:text-navy" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 17.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">ATS Pipeline</span>
                        </div>
                        <svg x-show="sidebarOpen" :class="openDropdowns.recruitment ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openDropdowns.recruitment && sidebarOpen" x-cloak class="mt-1 pl-9 pr-2 space-y-1 text-xs border-l-2 border-slate-200 ml-5">
                        <a href="{{ route('admin.recruitment.index', ['tab' => 'pipeline']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Kanban Board</a>
                        <a href="{{ route('admin.recruitment.index', ['tab' => 'interviews']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Interview Schedules</a>
                        <a href="{{ route('admin.recruitment.index', ['tab' => 'offers']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Offer Letters</a>
                    </div>
                </div>

                {{-- 6. Subscriptions & Pay --}}
                <div>
                    <button @click="toggleDropdown('commercial')" type="button" 
                            @class([
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group cursor-pointer',
                                'bg-slate-100 text-navy font-bold' => request()->routeIs('admin.subscriptions.*', 'admin.payments.*', 'admin.invoices.*', 'admin.operations.*'),
                                'text-slate-700 hover:text-navy hover:bg-slate-100' => !request()->routeIs('admin.subscriptions.*', 'admin.payments.*', 'admin.invoices.*', 'admin.operations.*'),
                            ])>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 text-slate-500 group-hover:text-navy" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 002.25 19.5z" />
                            </svg>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">Subscriptions & Pay</span>
                        </div>
                        <svg x-show="sidebarOpen" :class="openDropdowns.commercial ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openDropdowns.commercial && sidebarOpen" x-cloak class="mt-1 pl-9 pr-2 space-y-1 text-xs border-l-2 border-slate-200 ml-5">
                        <a href="{{ route('admin.subscriptions.index') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Employer Plans</a>
                        <a href="{{ route('admin.payments.index') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Payment Logs</a>
                        <a href="{{ route('admin.operations.index', 'packages') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Pricing Packages</a>
                    </div>
                </div>

                {{-- 7. AI & APIs --}}
                <div>
                    <button @click="toggleDropdown('ai')" type="button" 
                            @class([
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group cursor-pointer',
                                'bg-slate-100 text-navy font-bold' => request()->routeIs('admin.ai-api.*'),
                                'text-slate-700 hover:text-navy hover:bg-slate-100' => !request()->routeIs('admin.ai-api.*'),
                            ])>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 text-slate-500 group-hover:text-navy" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>
                            </svg>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">AI & APIs</span>
                        </div>
                        <svg x-show="sidebarOpen" :class="openDropdowns.ai ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openDropdowns.ai && sidebarOpen" x-cloak class="mt-1 pl-9 pr-2 space-y-1 text-xs border-l-2 border-slate-200 ml-5">
                        <a href="{{ route('admin.ai-api.index', ['view' => 'global-ai-settings']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Feature Flags</a>
                        <a href="{{ route('admin.ai-api.index', ['view' => 'platform-ai']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Model Providers</a>
                        <a href="{{ route('admin.ai-api.index', ['view' => 'resume-parser']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Resume Parser</a>
                    </div>
                </div>

                {{-- 8. Masters & Feeds --}}
                <div>
                    <button @click="toggleDropdown('masters')" type="button" 
                            @class([
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group cursor-pointer',
                                'bg-slate-100 text-navy font-bold' => request()->routeIs('admin.records.*', 'admin.masters.*', 'admin.external-data.*'),
                                'text-slate-700 hover:text-navy hover:bg-slate-100' => !request()->routeIs('admin.records.*', 'admin.masters.*', 'admin.external-data.*'),
                            ])>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 text-slate-500 group-hover:text-navy" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
                            </svg>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">Masters & Feeds</span>
                        </div>
                        <svg x-show="sidebarOpen" :class="openDropdowns.masters ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openDropdowns.masters && sidebarOpen" x-cloak class="mt-1 pl-9 pr-2 space-y-1 text-xs border-l-2 border-slate-200 ml-5">
                        <a href="{{ route('admin.masters.index', 'job-categories') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Job Categories</a>
                        <a href="{{ route('admin.masters.index', 'countries') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Countries & Currencies</a>
                        <a href="{{ route('admin.external-data.index') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">External Data Feeds</a>
                    </div>
                </div>

                {{-- 9. CMS & Blog --}}
                <div>
                    <button @click="toggleDropdown('cms')" type="button" 
                            @class([
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group cursor-pointer',
                                'bg-slate-100 text-navy font-bold' => request()->routeIs('admin.cms.*', 'admin.blogs.*'),
                                'text-slate-700 hover:text-navy hover:bg-slate-100' => !request()->routeIs('admin.cms.*', 'admin.blogs.*'),
                            ])>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 text-slate-500 group-hover:text-navy" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/>
                            </svg>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">CMS & Blog</span>
                        </div>
                        <svg x-show="sidebarOpen" :class="openDropdowns.cms ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openDropdowns.cms && sidebarOpen" x-cloak class="mt-1 pl-9 pr-2 space-y-1 text-xs border-l-2 border-slate-200 ml-5">
                        <a href="{{ route('admin.blogs.index') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Recruitment Blog</a>
                        <a href="{{ route('admin.operations.index', 'sliders') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Website Sliders</a>
                        <a href="{{ route('admin.cms.index') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">CMS Blocks</a>
                    </div>
                </div>

                {{-- 10. Settings & Branding --}}
                <div>
                    <button @click="toggleDropdown('settings')" type="button" 
                            @class([
                                'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all group cursor-pointer',
                                'bg-slate-100 text-navy font-bold' => request()->routeIs('admin.site-settings.*'),
                                'text-slate-700 hover:text-navy hover:bg-slate-100' => !request()->routeIs('admin.site-settings.*'),
                            ])>
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0 text-slate-500 group-hover:text-navy" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
                            </svg>
                            <span x-show="sidebarOpen" class="whitespace-nowrap">Settings & Branding</span>
                        </div>
                        <svg x-show="sidebarOpen" :class="openDropdowns.settings ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="openDropdowns.settings && sidebarOpen" x-cloak class="mt-1 pl-9 pr-2 space-y-1 text-xs border-l-2 border-slate-200 ml-5">
                        <a href="{{ route('admin.site-settings.edit') }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Branding, Logo & SEO</a>
                        <a href="{{ route('admin.control-center.index', ['section' => 'users-permissions', 'view' => 'admin-users']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Admin Users & Roles</a>
                        <a href="{{ route('admin.control-center.index', ['section' => 'audit-logs', 'view' => 'admin-activity']) }}" class="block py-1.5 px-2 rounded-lg text-slate-600 hover:text-navy hover:bg-slate-100 font-medium">Security & Audit Logs</a>
                    </div>
                </div>
            </nav>

            {{-- Sidebar Footer --}}
            <div class="border-t border-border p-3 bg-slate-50/80">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-9 h-9 rounded-xl bg-navy text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                        {{ substr(auth()->user()->name ?? 'Admin', 0, 2) }}
                    </div>
                    <div x-show="sidebarOpen" class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-navy truncate">{{ auth()->user()->name ?? 'Luckyboss Admin' }}</p>
                        <span class="inline-block text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200 font-bold uppercase tracking-wider">Super Admin</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="flex items-center gap-2.5 w-full px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                        <span x-show="sidebarOpen">Sign Out</span>
                    </button>
                </form>
            </div>

            {{-- Collapse Toggle Button --}}
            <button @click="sidebarOpen = !sidebarOpen" class="absolute top-18 -right-3 w-6 h-6 rounded-full bg-white border border-border shadow-md flex items-center justify-center hover:bg-surface transition-colors cursor-pointer">
                <svg :class="sidebarOpen ? '' : 'rotate-180'" class="w-3.5 h-3.5 text-navy transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
        </aside>

        {{-- Main Page Container --}}
        <div class="flex-1 lg:ml-64 flex flex-col min-w-0">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-border h-16 flex items-center justify-between px-6 shadow-2xs">
                <div class="flex items-center gap-4">
                    <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-surface-sunken">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>

                    <div>
                        <h1 class="text-lg font-heading font-bold text-navy leading-tight">
                            {{ $heading ?? $title }}
                        <div>
                    </div>
                </div>

                {{-- Header Actions --}}
                <div class="flex items-center gap-4">
                    {{-- Notification Bell with Live Database Feed & Audio Chimes --}}
                    <div class="relative" x-data="notificationCenter()">
                        <button @click="toggle()" @mouseenter="playChime('system_alert')" type="button" class="relative p-2 rounded-xl text-slate-600 hover:text-navy hover:bg-slate-100 transition-colors cursor-pointer" title="Platform Notifications">
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="hidden md:block">
                            <label for="admin-menu-search" class="sr-only">Search menu</label>
                            <input id="admin-menu-search" name="menu" type="search" placeholder="Search menu" class="form-input w-40 py-1.5 text-xs">
                        </form>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span x-show="unreadCount > 0" class="absolute top-1 right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-500 text-[10px] font-bold text-white items-center justify-center" x-text="unreadCount"></span>
                            </span>
                        </button>

                        {{-- Dropdown Panel --}}
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-border z-50 overflow-hidden">
                            <div class="p-4 bg-navy text-white flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold">Platform Notifications</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white" x-text="unreadCount + ' new'"></span>
                                </div>
                                <button @click="markAllAsRead()" class="text-xs text-secondary-300 hover:text-white transition-colors cursor-pointer">
                                    Mark all read
                                </button>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-border">
                                <template x-for="n in notifications" :key="n.id">
                                    <div @click="playChime(n.type)" class="p-4 hover:bg-slate-50 transition-colors cursor-pointer flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" :class="n.unread ? 'bg-secondary-100 text-secondary-700' : 'bg-slate-100 text-slate-500'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-navy" x-text="n.title"></p>
                                            <p class="text-xs text-text-secondary mt-0.5 line-clamp-2" x-text="n.body"></p>
                                            <span class="text-[10px] text-text-muted mt-1 block" x-text="n.time"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="p-3 bg-slate-50 border-t border-border text-center">
                                <a href="{{ route('admin.notifications.index') }}" class="text-xs font-bold text-accent hover:underline">
                                    Open Full Notification Hub →
                                </a>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('home') }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border hover:border-accent text-xs font-semibold text-text-secondary hover:text-accent transition-colors">
                        <span>View Public Website</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>

                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-bold text-text-secondary hidden md:inline">Live Production</span>
                    </div>
                </div>
            </header>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="m-6 mb-0 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="m-6 mb-0 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm font-semibold flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            {{-- Page Content Slot --}}
            <main class="p-6 md:p-8 flex-1 overflow-x-hidden">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>