<?php $branding = app(\App\Services\SiteSettingsService::class)->branding(); ?>
<header 
    x-data="{ 
        open: false,
        scrolled: false
    }" 
    x-init="scrolled = (window.pageYOffset > 20)"
    @scroll.window="scrolled = (window.pageYOffset > 20)"
    :class="scrolled ? 'bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-md' : 'bg-white border-b border-slate-200 shadow-xs'"
    class="sticky top-0 z-50 transition-all duration-300 text-slate-800"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4 transition-all duration-300"
             :style="scrolled ? 'min-height: 72px; padding: 8px 0;' : 'min-height: 88px; padding: 12px 0;'">
            {{-- Brand Logo (Prominent, High-Visibility Display) --}}
            <a href="{{ route('home') }}" class="flex items-center flex-shrink-0 group focus:outline-none" style="padding: 2px 0;">
                <div class="relative flex items-center bg-white rounded-xl">
                    <img 
                        src="{{ asset($branding['logo_url']) }}" 
                        alt="Luckyboss Employment Agency Pte. Ltd" 
                        class="w-auto object-contain transition-all duration-300 group-hover:scale-102"
                        :style="scrolled ? 'height: 48px; max-height: 54px; max-width: 260px;' : 'height: 60px; max-height: 68px; max-width: 320px;'"
                        loading="eager"
                    >
                </div>
            </a>

            {{-- Navigation. A signed-in candidate gets their own four
                 destinations instead of the marketing links — TickBig swap
                 theirs for the vertical switcher in the same slot, and Explore
                 or Blog is not what someone signed in is here for. --}}
            @php
                $navSeeker = auth()->check() && auth()->user()->hasRole('job-seeker');
                // An employer gets the same treatment for the same reason: the
                // marketing links are not what someone signed in came here for,
                // and the hiring agent is their front door the way Lucky AI is
                // the candidate's.
                $navEmployer = auth()->check() && auth()->user()->hasRole('employer');
            @endphp

            @if ($navEmployer)
                {{-- sm:flex, not md/lg — both of those are absent from the
                     prebuilt bundle and resolve to display:none at every width.
                     Same finding as the candidate nav below. --}}
                <nav class="hidden sm:flex items-center gap-1 p-1 rounded-2xl border border-slate-200 bg-slate-50/90 text-slate-700 shadow-inner">
                    @foreach ([
                        ['label' => 'Hiring AI', 'url' => route('employer.home'), 'active' => request()->routeIs('employer.home') || request()->routeIs('employer.chat.*')],
                        // "Posted Jobs", not "Jobs" — an employer's own list, not
                        // the public board. It was the same page under a name that
                        // read like the seeker's job search.
                        ['label' => 'Posted Jobs', 'url' => route('employer.jobs.index'), 'active' => request()->routeIs('employer.jobs.*')],
                        ['label' => 'Candidates', 'url' => route('employer.portal', 'candidates'), 'active' => request()->route('section') === 'candidates'],
                        ['label' => 'Interviews', 'url' => route('employer.portal', 'interviews'), 'active' => request()->route('section') === 'interviews'],
                        // Offers moves to the account menu and Subscription takes
                        // the slot: what an employer is paying for and what is
                        // left of it is checked far more often than an offer log.
                        ['label' => 'Subscription', 'url' => route('employer.subscription'), 'active' => request()->routeIs('employer.subscription*')],
                    ] as $navItem)
                        <a href="{{ $navItem['url'] }}"
                           class="px-3.5 py-1.5 text-xs rounded-xl transition-all duration-200 transform hover:-translate-y-0.5 {{ $navItem['active'] ? 'bg-navy text-white shadow-xs font-bold' : 'font-semibold text-slate-700 hover:text-navy hover:bg-slate-200/70' }}">
                            {{ $navItem['label'] }}
                        </a>
                    @endforeach
                </nav>
            @elseif ($navSeeker)
                {{-- sm, not xl — and sm because it is the only breakpoint that
                     works. Probed in the browser: `md:flex` and `lg:flex` are
                     both absent from the prebuilt bundle and resolve to
                     display:none at any width, so they fail silently. Only
                     `sm:flex` and `xl:flex` exist. The marketing nav below
                     carries long words ("Opportunities", "Employers") and keeps
                     xl; these five are short, and they are the only way a
                     signed-in candidate moves around — at xl they were invisible
                     on every laptop window under 1280px. --}}
                <nav class="hidden sm:flex items-center gap-1 p-1 rounded-2xl border border-slate-200 bg-slate-50/90 text-slate-700 shadow-inner">
                    @foreach ([
                        ['label' => 'Lucky AI', 'url' => route('seeker.home'), 'active' => request()->routeIs('seeker.home') || request()->routeIs('seeker.chat.*')],
                        ['label' => 'Jobs', 'url' => route('jobs.index'), 'active' => request()->routeIs('jobs.*')],
                        ['label' => 'Matches', 'url' => route('seeker.resume.matches'), 'active' => request()->routeIs('seeker.resume.matches')],
                        ['label' => 'Applied', 'url' => route('seeker.dashboard', ['tab' => 'applications']), 'active' => request('tab') === 'applications'],
                        ['label' => 'Saved', 'url' => route('seeker.dashboard', ['tab' => 'saved']), 'active' => request('tab') === 'saved'],
                    ] as $navItem)
                        <a href="{{ $navItem['url'] }}"
                           class="px-3.5 py-1.5 text-xs rounded-xl transition-all duration-200 transform hover:-translate-y-0.5 {{ $navItem['active'] ? 'bg-navy text-white shadow-xs font-bold' : 'font-semibold text-slate-700 hover:text-navy hover:bg-slate-200/70' }}">
                            {{ $navItem['label'] }}
                        </a>
                    @endforeach
                </nav>
            @else
            {{-- Clean Desktop Navigation with Smooth Animated Tab Transitions --}}
            <nav class="hidden xl:flex items-center gap-1 p-1 rounded-2xl border border-slate-200 bg-slate-50/90 text-slate-700 shadow-inner">
                {{-- 1. Home --}}
                <a href="{{ route('home') }}" 
                   class="px-3.5 py-1.5 text-xs rounded-xl transition-all duration-200 transform hover:-translate-y-0.5 {{ request()->routeIs('home') ? 'bg-navy text-white shadow-xs font-bold' : 'font-semibold text-slate-700 hover:text-navy hover:bg-slate-200/70' }}">
                    Home
                </a>

                {{-- 2. Explore — scrolls the home page rather than leaving it.
                     From any other page it goes home first and lands on the
                     same section. --}}
                <a href="{{ request()->routeIs('home') ? '#browse' : route('home') . '#browse' }}"
                   class="px-3.5 py-1.5 text-xs rounded-xl transition-all duration-200 transform hover:-translate-y-0.5 font-semibold text-slate-700 hover:text-navy hover:bg-slate-200/70">
                    Explore
                </a>

                {{-- 3. Opportunities — scrolls to the Featured Opportunities
                     section, the same way Explore scrolls to #browse.

                     This used to send a signed-out visitor straight to the
                     sign-in form. Asking for an account before showing a single
                     vacancy is the wrong order: the listings are what makes the
                     account worth creating. The gate now sits on opening an
                     individual job, which is where TickBig puts it too. --}}
                <a href="{{ request()->routeIs('home') ? '#opportunities' : route('home') . '#opportunities' }}"
                   class="px-3.5 py-1.5 text-xs rounded-xl transition-all duration-200 transform hover:-translate-y-0.5 {{ request()->routeIs('jobs.*') ? 'bg-navy text-white shadow-xs font-bold' : 'font-semibold text-slate-700 hover:text-navy hover:bg-slate-200/70' }}">
                    Opportunities
                </a>

                {{-- 4. Employers --}}
                <a href="{{ route('employers.public') }}"
                   class="px-3.5 py-1.5 text-xs rounded-xl transition-all duration-200 transform hover:-translate-y-0.5 {{ request()->routeIs('employers.*') ? 'bg-navy text-white shadow-xs font-bold' : 'font-semibold text-slate-700 hover:text-navy hover:bg-slate-200/70' }}">
                    Employers
                </a>

                {{-- 6. Blog --}}
                <a href="{{ route('blogs.index') }}" 
                   class="px-3.5 py-1.5 text-xs rounded-xl transition-all duration-200 transform hover:-translate-y-0.5 {{ request()->routeIs('blogs.*') ? 'bg-navy text-white shadow-xs font-bold' : 'font-semibold text-slate-700 hover:text-navy hover:bg-slate-200/70' }}">
                    Blog
                </a>
            </nav>

            @endif

            {{-- Right Actions: Sign In / Register / Dashboard --}}
            <div class="hidden sm:flex items-center gap-3 shrink-0">
                <?php if (auth()->check()) {
                    $user = auth()->user();
                    $dashboardUrl = $user->hasRole('super-admin')
                        ? route('admin.dashboard')
                        : ($user->hasRole('employer') ? route('employer.dashboard') : route('seeker.dashboard'));
                ?>
                    {{--
                        The signed-in icon rail.

                        TickBig puts seven circular icons here; we carry the two
                        that are backed by something real. The bell reads
                        /notifications/feed, which already existed and which
                        nothing in the site had ever called — the endpoint, the
                        model and eight rows of live data were all sitting there
                        unused.

                        Deliberately NOT here: a messages icon. `PortalMessage`
                        maps to a `messages` table that has no routes, no
                        controller and zero rows. An envelope that opens on
                        nothing is the kind of decoration this project has been
                        burned by before, so messaging stays out until it has a
                        backend.

                        Inline x-data rather than Alpine.data() — see CLAUDE.md,
                        the bundle assigns window.Alpine too late for component
                        registration, and inline state is the proven path.
                    --}}
                    <div class="relative"
                         x-data="{
                            open: false,
                            loaded: false,
                            items: [],
                            unread: 0,
                            async load() {
                                if (this.loaded) return;
                                this.loaded = true;
                                try {
                                    const r = await fetch('{{ route('notifications.feed') }}', { headers: { 'Accept': 'application/json' } });
                                    if (!r.ok) return;
                                    const j = await r.json();
                                    this.items = j.notifications || [];
                                    this.unread = j.unreadCount || 0;
                                } catch (e) { /* a failed poll must not break the header */ }
                            },
                            async clear() {
                                try {
                                    await fetch('{{ route('notifications.clear-all') }}', {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                    });
                                    this.items = this.items.map(i => ({ ...i, unread: false }));
                                    this.unread = 0;
                                } catch (e) {}
                            }
                         }"
                         x-init="load()"
                         @click.away="open = false">
                        <button type="button" @click="open = !open"
                                class="relative flex items-center justify-center transition-colors cursor-pointer"
                                style="width:38px;height:38px;border-radius:9999px;border:1px solid #DCE6F3;background:#fff;color:#031F49;"
                                :aria-expanded="open" aria-label="Notifications">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                            </svg>
                            <span x-show="unread > 0" x-cloak x-text="unread > 9 ? '9+' : unread"
                                  style="position:absolute;top:-2px;right:-2px;min-width:18px;height:18px;padding:0 4px;border-radius:9999px;background:#18A66A;color:#fff;font-size:10px;font-weight:700;line-height:18px;text-align:center;border:2px solid #fff;"></span>
                        </button>

                        <div x-show="open" x-cloak
                             class="absolute right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50"
                             style="width:340px;max-width:calc(100vw - 32px);">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
                                <span class="text-xs font-bold uppercase tracking-wider" style="color:#6E829C;">Notifications</span>
                                <button type="button" @click="clear()" x-show="unread > 0"
                                        class="text-xs font-bold hover:underline cursor-pointer" style="color:#18A66A;">
                                    Mark all read
                                </button>
                            </div>

                            <div style="max-height:340px;overflow-y:auto;">
                                <template x-if="items.length === 0">
                                    <p class="px-4 py-8 text-center text-xs" style="color:#6E829C;">
                                        Nothing yet. Updates on your applications land here.
                                    </p>
                                </template>

                                <template x-for="n in items" :key="n.id">
                                    <div class="px-4 py-3 border-b border-slate-100"
                                         :style="n.unread ? 'background:#F7FAFF;' : ''">
                                        <div class="flex items-start gap-2.5">
                                            <span style="width:7px;height:7px;border-radius:9999px;margin-top:6px;flex:none;"
                                                  :style="n.unread ? 'background:#18A66A;' : 'background:#DCE6F3;'"></span>
                                            <div style="min-width:0;">
                                                <p class="text-xs font-bold" style="color:#031F49;" x-text="n.title"></p>
                                                <p class="text-xs mt-0.5" style="color:#46586F;" x-text="n.body"></p>
                                                <p class="text-xs mt-1" style="color:#9AA8BA;font-size:11px;" x-text="n.time"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Search. Third of the three icons that are backed by
                         something real; everything else TickBig puts up here we
                         have no endpoint for. --}}
                    <a href="{{ route('jobs.index') }}"
                       class="flex items-center justify-center transition-colors"
                       style="width:38px;height:38px;border-radius:9999px;border:1px solid #DCE6F3;color:#46586F;"
                       aria-label="Search jobs" title="Search jobs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </a>

                    {{-- Account menu, opened by hovering the avatar. Click still
                         works: hover does not exist on a phone, and this is the
                         only way into Sign Out. --}}
                    <div class="relative" x-data="{ profileOpen: false }"
                         @mouseenter="profileOpen = true" @mouseleave="profileOpen = false"
                         @click.away="profileOpen = false">
                        <button type="button" @click="profileOpen = !profileOpen"
                                class="flex items-center justify-center transition-colors cursor-pointer"
                                style="width:38px;height:38px;border-radius:9999px;border:1px solid #DCE6F3;background:#031F49;color:#fff;font-weight:700;font-size:13px;"
                                :aria-expanded="profileOpen" aria-label="Account menu">
                            {{ strtoupper(mb_substr(trim($user->name) !== '' ? $user->name : 'U', 0, 1)) }}
                        </button>

                        {{-- No margin gap between the button and the panel: a
                             1px dead zone makes a hover menu impossible to
                             reach. The panel starts flush and pads itself. --}}
                        <div x-show="profileOpen" x-cloak
                             class="absolute right-0 w-64 bg-white rounded-2xl shadow-md border border-slate-200 py-2 z-50"
                             style="top:100%;padding-top:10px;">
                            <div class="px-4 py-2 border-b border-slate-200 mb-1">
                                <p class="text-xs font-bold truncate" style="color:#031F49;">{{ $user->name }}</p>
                                <p class="text-xs truncate" style="color:#6E829C;">{{ $user->email }}</p>
                            </div>

@if ($user->hasRole('job-seeker'))
                            {{-- The two things a candidate came to do, lifted and
                                 coloured, then the browsing groups — TickBig's
                                 order, in our palette. --}}
                            @foreach ([
                                ['label' => 'Lucky AI', 'url' => route('seeker.home'), 'icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z'],
                                ['label' => 'My Resume', 'url' => route('seeker.resume.choose'), 'icon' => 'M7 16a4 4 0 01-.88-7.9A5 5 0 1115.9 6H16a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12'],
                            ] as $item)
                                <a href="{{ $item['url'] }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-bold text-accent hover:bg-slate-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach

                            <div x-data="{ jobs: false }">
                                <button type="button" @click="jobs = ! jobs"
                                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-bold text-navy hover:bg-slate-50 transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <span class="flex-1 text-left">Jobs</span>
                                    <svg x-show="! jobs" class="w-4 h-4" style="color:#9AA8BA;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    <svg x-show="jobs" x-cloak class="w-4 h-4 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <div x-show="jobs" x-cloak>
                                    @foreach ([
                                        ['label' => 'Find jobs', 'url' => route('jobs.index')],
                                        ['label' => 'Jobs that fit you', 'url' => route('seeker.resume.matches')],
                                        ['label' => 'Applied', 'url' => route('seeker.dashboard', ['tab' => 'applications'])],
                                        ['label' => 'Saved', 'url' => route('seeker.dashboard', ['tab' => 'saved'])],
                                    ] as $child)
                                        <a href="{{ $child['url'] }}" class="block pl-11 pr-4 py-2 text-xs font-semibold text-navy hover:bg-slate-50 transition-colors">{{ $child['label'] }}</a>
                                    @endforeach
                                </div>
                            </div>

                            @foreach ([
                                ['label' => 'Auto Apply', 'url' => route('seeker.auto-apply.edit'), 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                                ['label' => 'My profile', 'url' => route('seeker.profile.show'), 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            ] as $item)
                                <a href="{{ $item['url'] }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-bold text-navy hover:bg-slate-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
@elseif ($user->hasRole('employer'))
                            {{-- Everything an employer can reach that is not one of
                                 the five pills. Before this the whole menu was a
                                 single "Dashboard" link, so Company Profile, Team,
                                 Billing, Offers and Analytics were reachable only
                                 from the old left rail — which is exactly why
                                 retiring that rail needed this list first. --}}
                            @foreach ([
                                ['label' => 'Post a vacancy', 'url' => route('employer.jobs.create'), 'icon' => 'M12 4v16m8-8H4'],
                                ['label' => 'Dashboard', 'url' => route('employer.dashboard'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            ] as $item)
                                <a href="{{ $item['url'] }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-bold text-accent hover:bg-slate-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach

                            <div class="border-t border-slate-200 mt-1 pt-2">
                                <p class="px-4 pb-1 text-[10px] font-extrabold uppercase tracking-wider" style="color:#9AA8BA;">Hiring</p>
                                @foreach ([
                                    ['label' => 'Offers', 'url' => route('employer.portal', 'offers')],
                                    ['label' => 'Recruitment pipeline', 'url' => route('employer.portal', 'recruitment')],
                                    ['label' => 'Analytics', 'url' => route('employer.portal', 'analytics')],
                                ] as $item)
                                    <a href="{{ $item['url'] }}" class="block px-4 py-2 text-xs font-semibold text-navy hover:bg-slate-50 transition-colors">{{ $item['label'] }}</a>
                                @endforeach
                            </div>

                            <div class="border-t border-slate-200 mt-1 pt-2">
                                <p class="px-4 pb-1 text-[10px] font-extrabold uppercase tracking-wider" style="color:#9AA8BA;">Company</p>
                                @foreach ([
                                    ['label' => 'Company profile', 'url' => route('employer.portal', 'company-profile')],
                                    ['label' => 'Team & users', 'url' => route('employer.portal', 'team')],
                                    ['label' => 'Subscription & credits', 'url' => route('employer.subscription')],
                                    ['label' => 'Billing', 'url' => route('employer.portal', 'billing')],
                                ] as $item)
                                    <a href="{{ $item['url'] }}" class="block px-4 py-2 text-xs font-semibold text-navy hover:bg-slate-50 transition-colors">{{ $item['label'] }}</a>
                                @endforeach
                            </div>
@else
                            <a href="{{ $dashboardUrl }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-bold text-navy hover:bg-slate-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                <span>Dashboard</span>
                            </a>
@endif

                            {{-- The settings tail, straight from TickBig's drawer:
                                 Settings / Subscription / Contact / About /
                                 Privacy & Terms, then Logout. Ours drops
                                 Subscription — candidates are not billed for
                                 anything, and a billing link on a free account
                                 is furniture for something that does not exist.
                                 These pages already existed and were reachable
                                 only from the footer, which the conversation
                                 screen no longer has. --}}
                            <div class="border-t border-slate-200 mt-1 pt-2">
                                <p class="px-4 pb-1 text-[10px] font-extrabold uppercase tracking-wider" style="color:#9AA8BA;">Settings</p>
                                @foreach ([
                                    ['label' => 'About Lucky Boss', 'url' => route('page.show', 'about-us')],
                                    ['label' => 'Contact', 'url' => route('contact.public')],
                                    ['label' => 'Privacy & Terms', 'url' => route('page.show', 'privacy-policy')],
                                ] as $item)
                                    <a href="{{ $item['url'] }}" class="block px-4 py-2 text-xs font-semibold text-navy hover:bg-slate-50 transition-colors">{{ $item['label'] }}</a>
                                @endforeach
                            </div>

                            <div class="border-t border-slate-200 mt-1 pt-1"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs font-bold text-navy hover:bg-slate-50 transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12"></path></svg>
                                    <span>Sign out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php } else { ?>
                    <a href="{{ route('login') }}" 
                       class="text-xs font-bold px-3 py-1.5 text-navy hover:text-secondary-600 transition-colors">
                        Sign In
                    </a>

                    {{--
                        One button, straight to the account-type chooser at
                        /register.

                        This was a dropdown offering "Job Seeker Register" and
                        "Employer Register". It asked the same question the
                        chooser page asks, but in a 208px menu — and because it
                        linked to the two forms directly, the chooser page was
                        orphaned: nothing in the site linked to it. TickBig has
                        one Sign Up button that opens the choice as a real page,
                        which is the behaviour sir asked for, and it is the
                        better one: the decision picks which portal you end up
                        in, so it deserves the full width rather than a menu.
                    --}}
                    <a href="{{ route('register') }}"
                       class="btn btn-primary btn-sm py-1.5 px-4 font-bold text-xs shadow-md cursor-pointer hover:scale-102 transition-transform">
                        Register
                    </a>
                <?php } ?>
            </div>

            {{-- Mobile / Tablet Hamburger --}}
            <button 
                @click="open = !open" 
                {{-- Hidden as soon as the nav it replaces is on screen. A signed-in
                         candidate's nav appears at sm, the marketing nav at xl,
                         so the burger has to follow whichever is in use — otherwise
                         both show at once. --}}
                    class="{{ $navSeeker ? 'sm:hidden' : 'xl:hidden' }} flex h-10 w-10 shrink-0 items-center justify-center p-2 rounded-xl transition-colors border border-slate-200 text-slate-800 hover:bg-slate-100 cursor-pointer" 
                aria-label="Menu"
            >
                <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                <svg x-show="open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- Mobile Dropdown Menu --}}
        <div 
            x-show="open" 
            x-cloak 
            x-transition:enter="transition ease-out duration-150" 
            x-transition:enter-start="opacity-0 -translate-y-2" 
            x-transition:enter-end="opacity-100 translate-y-0" 
            x-transition:leave="transition ease-in duration-100" 
            x-transition:leave-start="opacity-100 translate-y-0" 
            x-transition:leave-end="opacity-0 -translate-y-2" 
            class="xl:hidden border-t border-slate-200 bg-white text-slate-800 py-5 space-y-1 rounded-b-2xl shadow-xl"
        >
            <a href="{{ route('home') }}" class="block px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100 rounded-xl">Home</a>
            {{-- Mirrors the desktop nav. It kept the old five links after the
                 desktop set was cut, so a phone saw a different site. --}}
            <a href="{{ request()->routeIs('home') ? '#browse' : route('home') . '#browse' }}" class="block px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100 rounded-xl">Explore</a>
            <a href="{{ request()->routeIs('home') ? '#opportunities' : route('home') . '#opportunities' }}" class="block px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100 rounded-xl">Opportunities</a>
            <a href="{{ route('employers.public') }}" class="block px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100 rounded-xl">Employers</a>
            <a href="{{ route('blogs.index') }}" class="block px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100 rounded-xl">Blog</a>
            
            <div class="border-t border-slate-200 pt-3 mt-3 px-4 flex flex-col gap-2">
                <?php if (auth()->check()) { $user = auth()->user(); ?>
                    <p class="text-sm font-semibold text-slate-600">Hi, {{ $user->name }}</p>
                    <a href="{{ $user->hasRole('super-admin') ? route('admin.dashboard') : ($user->hasRole('employer') ? route('employer.dashboard') : route('seeker.dashboard')) }}" class="btn btn-primary btn-sm w-full text-center font-bold">
                        Dashboard
                    </a>
                <?php } else { ?>
                    <a href="{{ route('login') }}" class="btn btn-outline btn-sm w-full text-center border-slate-300 text-slate-800 font-bold">
                        Sign In
                    </a>
                    {{-- Same chooser as the desktop button. This went straight
                         to the seeker form, so an employer on a phone was
                         signed up as a candidate without ever being asked. --}}
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm w-full text-center font-bold">
                        Create an account
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
</header>