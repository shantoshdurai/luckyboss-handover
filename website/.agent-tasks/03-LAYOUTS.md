# Task 03: Layout System Overhaul

## Context
You are working on **Lucky Boss Portal** at `c:\Luckyboss\luckyboss-app`. Laravel 12 + Tailwind CSS 4 + Alpine.js. Tasks 01 and 02 have set up the design system and component library.

**Current state of layouts:**
- `resources/views/layouts/app.blade.php` — 16 lines, ALL inline `<style>`, uses Georgia/Arial fonts, no `@vite`, no meta tags
- `resources/views/components/admin-layout.blade.php` — 12KB, contains 100+ FAKE menu items all pointing to one placeholder route
- `resources/views/components/employer-sidebar.blade.php` — 12KB, massive inline styles
- `resources/views/components/seeker-sidebar.blade.php` — 6KB, inline styles
- `resources/views/components/public-header.blade.php` — 2KB, basic header
- `resources/views/components/footer.blade.php` — 3KB

**CRITICAL**: Every layout MUST include `@vite(['resources/css/app.css', 'resources/js/app.js'])` in the `<head>`.

**Brand:**
- Primary: `#031f49` (navy), Secondary: `#18a66a` (green), Accent: `#2563eb`
- Fonts: Inter (body), Plus Jakarta Sans (headings) — loaded via CSS
- Logo text: "Lucky**Boss**" where Lucky is green and Boss is navy

**Existing routes** (from `routes/web.php`):
- Public: `/`, `/jobs`, `/job-categories`, `/employers`, `/job-seekers`, `/blog`, `/contact`, `/pages/{page}` (about-us, faq, terms, privacy, refund)
- Auth: `/login`, `/register/job-seeker`, `/register/employer`
- Admin: `/admin` (dashboard), `/admin/companies`, `/admin/jobs`, `/admin/candidates`, `/admin/subscriptions`, `/admin/payments`, `/admin/ai-api`, `/admin/interviews`, `/admin/communication`, `/admin/notifications`, `/admin/cms`, `/admin/blogs`, `/admin/reports`, `/admin/site-settings`, `/admin/records/{module}`, `/admin/masters/{master}`, `/admin/operations/{area}`
- Employer: `/employer` (dashboard), `/employer/jobs`, `/employer/portal/{section}`, `/employer/jobs/{job}/applicants`
- Seeker: `/job-seeker` (dashboard), `/job-seeker/profile`, `/job-seeker/jobs/{job}/save`, `/job-seeker/offers/{offer}/{response}`

---

## File 1: Public Layout

**File**: `c:\Luckyboss\luckyboss-app\resources\views\components\layouts\app.blade.php`

**OVERWRITE** the existing file entirely with:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Lucky Boss Portal | AI-Powered Recruitment' }}</title>
    <meta name="description" content="{{ $description ?? 'Find jobs, build your career, and manage recruitment with Lucky Boss Portal — your growth partner in hiring.' }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $title ?? 'Lucky Boss Portal' }}">
    <meta property="og:description" content="{{ $description ?? 'AI-Powered Recruitment Platform for Singapore, Malaysia, India and beyond.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('uploads/branding/favicon-20260821075904.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen flex flex-col bg-surface antialiased">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="fixed top-4 right-4 z-[100] animate-slide-down" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
            <x-ui.alert type="success" dismissible>{{ session('success') }}</x-ui.alert>
        </div>
    @endif
    @if(session('error'))
        <div class="fixed top-4 right-4 z-[100] animate-slide-down" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
            <x-ui.alert type="danger" dismissible>{{ session('error') }}</x-ui.alert>
        </div>
    @endif

    {{-- Header --}}
    <x-public-header />

    {{-- Page Content --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <x-footer />

    @stack('scripts')
</body>
</html>
```

---

## File 2: Public Header

**File**: `c:\Luckyboss\luckyboss-app\resources\views\components\public-header.blade.php`

**OVERWRITE** entirely:

```blade
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-border shadow-nav" x-data="mobileMenu()">
    <div class="container-app">
        <div class="flex items-center justify-between h-16 lg:h-[72px]">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 flex-shrink-0">
                @php
                    $branding = \App\Models\AdminRecord::where('slug', 'website-branding')->first();
                    $logoUrl = $branding ? json_decode($branding->payload, true)['logo_url'] ?? null : null;
                @endphp
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Lucky Boss" class="h-9 w-auto">
                @else
                    <span class="text-xl font-heading font-bold">
                        <span class="text-secondary-500">Lucky</span><span class="text-navy">Boss</span>
                    </span>
                @endif
            </a>

            {{-- Desktop Nav --}}
            <nav class="hidden lg:flex items-center gap-1">
                <a href="{{ route('jobs.index') }}" class="px-3 py-2 text-sm font-medium text-text-secondary hover:text-navy rounded-lg hover:bg-surface-sunken transition-colors {{ request()->routeIs('jobs.*') ? 'text-navy font-semibold' : '' }}">
                    Find Jobs
                </a>
                <a href="{{ route('categories.index') }}" class="px-3 py-2 text-sm font-medium text-text-secondary hover:text-navy rounded-lg hover:bg-surface-sunken transition-colors {{ request()->routeIs('categories.*') ? 'text-navy font-semibold' : '' }}">
                    Categories
                </a>
                <a href="{{ route('employers.public') }}" class="px-3 py-2 text-sm font-medium text-text-secondary hover:text-navy rounded-lg hover:bg-surface-sunken transition-colors">
                    Employers
                </a>
                <a href="{{ route('blogs.index') }}" class="px-3 py-2 text-sm font-medium text-text-secondary hover:text-navy rounded-lg hover:bg-surface-sunken transition-colors {{ request()->routeIs('blogs.*') ? 'text-navy font-semibold' : '' }}">
                    Blog
                </a>
                <a href="{{ route('contact.public') }}" class="px-3 py-2 text-sm font-medium text-text-secondary hover:text-navy rounded-lg hover:bg-surface-sunken transition-colors">
                    Contact
                </a>
            </nav>

            {{-- Right Actions --}}
            <div class="hidden lg:flex items-center gap-3">
                @auth
                    @php $user = auth()->user(); @endphp
                    <a href="{{ $user->hasRole('super-admin') ? route('admin.dashboard') : ($user->hasRole('employer') ? route('employer.dashboard') : route('seeker.dashboard')) }}"
                       class="btn btn-ghost btn-sm">
                        Dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Sign In</a>
                    <a href="{{ route('register.seeker') }}" class="btn btn-primary btn-sm">Register</a>
                    <a href="{{ route('register.employer') }}" class="btn btn-secondary btn-sm">For Employers</a>
                @endauth
            </div>

            {{-- Mobile Menu Button --}}
            <button @click="toggle()" class="lg:hidden p-2 rounded-lg hover:bg-surface-sunken transition-colors" aria-label="Menu">
                <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="lg:hidden border-t border-border py-4 space-y-1">
            <a href="{{ route('jobs.index') }}" class="block px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-surface-sunken">Find Jobs</a>
            <a href="{{ route('categories.index') }}" class="block px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-surface-sunken">Categories</a>
            <a href="{{ route('employers.public') }}" class="block px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-surface-sunken">Employers</a>
            <a href="{{ route('blogs.index') }}" class="block px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-surface-sunken">Blog</a>
            <a href="{{ route('contact.public') }}" class="block px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-surface-sunken">Contact</a>

            <div class="border-t border-border pt-4 mt-4 space-y-2">
                @auth
                    @php $user = auth()->user(); @endphp
                    <a href="{{ $user->hasRole('super-admin') ? route('admin.dashboard') : ($user->hasRole('employer') ? route('employer.dashboard') : route('seeker.dashboard')) }}" class="btn btn-primary w-full">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline w-full">Sign In</a>
                    <a href="{{ route('register.seeker') }}" class="btn btn-primary w-full">Register as Job Seeker</a>
                    <a href="{{ route('register.employer') }}" class="btn btn-secondary w-full">Register as Employer</a>
                @endauth
            </div>
        </div>
    </div>
</header>
```

---

## File 3: Footer

**File**: `c:\Luckyboss\luckyboss-app\resources\views\components\footer.blade.php`

**OVERWRITE** entirely:

```blade
@php
    $contact = \App\Models\AdminRecord::where('slug', 'official-contact')->first();
    $contactData = $contact ? json_decode($contact->payload, true) : [];
    $branding = \App\Models\AdminRecord::where('slug', 'website-branding')->first();
    $brandData = $branding ? json_decode($branding->payload, true) : [];
@endphp

<footer class="bg-navy text-white">
    {{-- Main Footer --}}
    <div class="container-app py-16">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-12">
            {{-- Brand Column --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <div class="mb-4">
                    @if(isset($brandData['logo_url']))
                        <img src="{{ $brandData['logo_url'] }}" alt="Lucky Boss" class="h-10 w-auto brightness-0 invert">
                    @else
                        <span class="text-2xl font-heading font-bold">
                            <span class="text-secondary-400">Lucky</span><span class="text-white">Boss</span>
                        </span>
                    @endif
                </div>
                <p class="text-sm text-slate-400 leading-relaxed max-w-xs">
                    Growth Partner in Your Hiring Journey. AI-powered recruitment for Singapore, Malaysia, India and beyond.
                </p>

                {{-- Social Links --}}
                <div class="flex items-center gap-3 mt-6">
                    @foreach(['facebook' => $contactData['facebook_url'] ?? '#', 'linkedin' => $contactData['linkedin_url'] ?? '#', 'instagram' => $contactData['instagram_url'] ?? '#', 'youtube' => $contactData['youtube_url'] ?? '#'] as $platform => $url)
                        @if($url && $url !== '#')
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-secondary-500 flex items-center justify-center transition-colors" aria-label="{{ ucfirst($platform) }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                @if($platform === 'facebook')
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                @elseif($platform === 'linkedin')
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                @elseif($platform === 'instagram')
                                    <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678a6.162 6.162 0 100 12.324 6.162 6.162 0 100-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.441 1.441 0 11-2.882 0 1.441 1.441 0 012.882 0z"/>
                                @elseif($platform === 'youtube')
                                    <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                @endif
                            </svg>
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">For Job Seekers</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('jobs.index') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Browse Jobs</a></li>
                    <li><a href="{{ route('categories.index') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Job Categories</a></li>
                    <li><a href="{{ route('register.seeker') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Create Account</a></li>
                    <li><a href="{{ route('blogs.index') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Career Blog</a></li>
                </ul>
            </div>

            {{-- Employer Links --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">For Employers</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('register.employer') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Post a Job</a></li>
                    <li><a href="{{ route('register.employer') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Subscription Plans</a></li>
                    <li><a href="{{ route('seekers.public') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Browse Candidates</a></li>
                    <li><a href="{{ route('contact.public') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Enterprise Plans</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Company</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('page.show', 'about-us') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">About Us</a></li>
                    <li><a href="{{ route('contact.public') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Contact</a></li>
                    <li><a href="{{ route('page.show', 'privacy-policy') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('page.show', 'terms-and-conditions') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Terms of Service</a></li>
                    <li><a href="{{ route('page.show', 'refund-policy') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Refund Policy</a></li>
                    <li><a href="{{ route('page.show', 'faq') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">FAQ</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Bottom Bar --}}
    <div class="border-t border-white/10">
        <div class="container-app py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-slate-500">&copy; {{ date('Y') }} Lucky Boss Portal. All rights reserved.</p>
            <p class="text-xs text-slate-500">Growth Partner in Your Hiring Journey</p>
        </div>
    </div>
</footer>
```

---

## File 4: Admin Layout

**File**: `c:\Luckyboss\luckyboss-app\resources\views\components\admin-layout.blade.php`

**OVERWRITE** entirely. This replaces the 100+ fake menu items with a clean, real sidebar:

```blade
@props(['title' => 'Admin'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — Lucky Boss Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface antialiased" x-data="{ sidebarOpen: true, mobileSidebarOpen: false }">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside
            :class="sidebarOpen ? 'w-64' : 'w-20'"
            class="hidden lg:flex flex-col fixed inset-y-0 left-0 z-30 bg-navy transition-all duration-300 overflow-hidden"
        >
            {{-- Logo --}}
            <div class="flex items-center h-16 px-5 border-b border-white/10 flex-shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-secondary-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">LB</div>
                    <span x-show="sidebarOpen" x-transition class="text-white font-heading font-bold text-base whitespace-nowrap">Lucky Boss</span>
                </a>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                @php
                    $nav = [
                        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['label' => 'Companies', 'route' => 'admin.companies.index', 'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
                        ['label' => 'Candidates', 'route' => 'admin.candidates.index', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
                        ['label' => 'Jobs', 'route' => 'admin.jobs.index', 'icon' => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0'],
                        ['label' => 'Recruitment', 'route' => 'admin.recruitment.index', 'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25'],
                        ['label' => 'Subscriptions', 'route' => 'admin.subscriptions.index', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z'],
                        ['label' => 'Payments', 'route' => 'admin.payments.index', 'icon' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['label' => 'AI & API', 'route' => 'admin.ai-api.index', 'icon' => 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5'],
                        ['label' => 'Blogs', 'route' => 'admin.blogs.index', 'icon' => 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5'],
                        ['label' => 'CMS', 'route' => 'admin.cms.index', 'icon' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z'],
                        ['label' => 'Reports', 'route' => 'admin.reports.index', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                        ['label' => 'Settings', 'route' => 'admin.site-settings.edit', 'icon' => 'M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z'],
                    ];
                @endphp

                @foreach($nav as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200',
                           'bg-white/10 text-white' => request()->routeIs($item['route'] . '*'),
                           'text-slate-400 hover:text-white hover:bg-white/5' => !request()->routeIs($item['route'] . '*'),
                       ])
                    >
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            {{-- Sidebar Footer --}}
            <div class="border-t border-white/10 p-3">
                <div class="flex items-center gap-3 px-3 py-2">
                    <x-ui.avatar :name="auth()->user()->name ?? 'Admin'" size="sm" />
                    <div x-show="sidebarOpen" x-transition class="min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <p class="text-xs text-slate-400 truncate">Super Admin</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 w-full px-3 py-2 rounded-xl text-sm text-slate-400 hover:text-white hover:bg-white/5 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                        <span x-show="sidebarOpen" x-transition>Sign Out</span>
                    </button>
                </form>
            </div>

            {{-- Collapse Toggle --}}
            <button @click="sidebarOpen = !sidebarOpen" class="absolute top-20 -right-3 w-6 h-6 rounded-full bg-white border border-border shadow-sm flex items-center justify-center hover:bg-surface-sunken transition-colors">
                <svg :class="sidebarOpen ? '' : 'rotate-180'" class="w-3 h-3 text-text-secondary transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </button>
        </aside>

        {{-- Main Content --}}
        <div :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'" class="flex-1 transition-all duration-300">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-border h-16 flex items-center px-6">
                <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="lg:hidden p-2 -ml-2 mr-3 rounded-lg hover:bg-surface-sunken">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                </button>

                <h1 class="text-lg font-heading font-semibold text-navy">{{ $title }}</h1>

                <div class="ml-auto flex items-center gap-3">
                    {{-- Search placeholder --}}
                    <div class="hidden md:block relative">
                        <input type="text" placeholder="Search..." class="form-input pl-9 py-1.5 w-56 text-sm">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="p-6">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-6"><x-ui.alert type="success" dismissible>{{ session('success') }}</x-ui.alert></div>
                @endif
                @if(session('error'))
                    <div class="mb-6"><x-ui.alert type="danger" dismissible>{{ session('error') }}</x-ui.alert></div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
```

---

## File 5: Employer Sidebar/Layout

**File**: `c:\Luckyboss\luckyboss-app\resources\views\components\employer-sidebar.blade.php`

**OVERWRITE** entirely. Follow the same pattern as the admin layout but with employer-specific navigation:
- Dashboard → `route('employer.dashboard')`
- My Jobs → `route('employer.jobs.index')`
- Post New Job → `route('employer.jobs.create')`  
- Candidates → `route('employer.portal', 'candidates')`
- Interviews → `route('employer.portal', 'interviews')`
- Offers → `route('employer.portal', 'offers')`
- Billing → `route('employer.portal', 'billing')`
- Team → `route('employer.portal', 'team')`
- Company Profile → `route('employer.portal', 'company-profile')`
- AI Tools → `route('employer.portal', 'ai-tools')`
- Analytics → `route('employer.portal', 'analytics')`

Use the **same layout structure** as admin layout (sidebar + topbar + content) but with:
- White sidebar instead of navy
- Green accent for active states
- Company name in sidebar header
- Subscription status badge under company name

The `employer-sidebar` component should be a FULL standalone layout (like admin-layout), not just a sidebar fragment. It wraps the entire page with `<!DOCTYPE html>`, `<head>`, `@vite`, etc.

Use the admin layout code as your template and adapt it. Use `@props(['title' => 'Employer Portal'])`.

---

## File 6: Seeker Sidebar/Layout

**File**: `c:\Luckyboss\luckyboss-app\resources\views\components\seeker-sidebar.blade.php`

**OVERWRITE** entirely. Same full-page layout pattern as employer but simpler nav:
- Dashboard → `route('seeker.dashboard')`
- My Applications → (link to dashboard, applications tab)
- Matching Jobs → (link to dashboard, matching tab)
- Saved Jobs → (link to dashboard, saved tab)
- My Profile → `route('seeker.profile.edit')`
- Notifications → (placeholder)
- Settings → (placeholder)

Use lighter, cleaner styling. Show profile completion percentage in sidebar.

---

## Verification

After creating all files, navigate to `http://localhost:8000/` (or the local dev URL) and verify:
1. The header renders correctly with navigation
2. The footer renders with proper columns
3. Admin panel at `/admin` has the clean sidebar
4. No PHP/Blade errors

If the server isn't running, just verify with `php artisan view:cache` to check for Blade compilation errors, then `php artisan view:clear`.

## IMPORTANT RULES
1. Every layout MUST have `@vite(['resources/css/app.css', 'resources/js/app.js'])` in `<head>`
2. NO inline `<style>` blocks — use ONLY Tailwind utility classes
3. Use `container-app` utility class (defined in our CSS) instead of the old `.container` class
4. Use the new component system: `<x-ui.button>`, `<x-ui.badge>`, `<x-ui.alert>`, `<x-ui.avatar>` etc.
5. All SVG icons use Heroicons style (24x24 viewBox, stroke-based)
