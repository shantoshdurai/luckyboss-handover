{{-- Every attribute this layout reads must be declared here.
     Declaring @props at all changes the rules: undeclared attributes stop
     being extracted as variables and go to $attributes instead. Adding only
     `bare` silently emptied $title and $description, so every page on the site
     fell through to the same site-wide SEO title — identical titles on the
     homepage, every vacancy and every blog post, which is as bad for search as
     having none. --}}
@props([
    'bare' => false,
    'title' => null,
    'description' => null,
    'image' => null,
    'imageAlt' => null,
])
<?php $branding = app(\App\Services\SiteSettingsService::class)->branding(); ?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? $branding['seo_title'] }}</title>
    <meta name="description" content="{{ $description ?? $branding['seo_description'] }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $title ?? 'Luckyboss Portal' }}">
    <meta property="og:description" content="{{ $description ?? 'AI-Powered Recruitment Platform for Singapore, Malaysia, India and beyond.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $image ?? asset($branding['logo_url']) }}">
    <meta property="og:image:alt" content="{{ $imageAlt ?? ($title ?? $branding['site_name']) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? $branding['seo_title'] }}">
    <meta name="twitter:description" content="{{ $description ?? $branding['seo_description'] }}">
    <meta name="twitter:image" content="{{ $image ?? asset($branding['logo_url']) }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset($branding['favicon_url']) }}">
    <script type="application/ld+json">@json(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $branding['site_name']])</script>

    {{-- Google Fonts: Anthropic/Claude Style Editorial Serif (Newsreader) + Plus Jakarta Sans --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,400..800;1,6..72,400..800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes pageEntrance {
            0% { opacity: 0; transform: translateY(6px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .page-transition-wrap {
            animation: pageEntrance 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        #nav-loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0%;
            background: linear-gradient(90deg, #f59e0b, #10b981, #031533);
            z-index: 99999;
            transition: width 0.3s ease, opacity 0.2s ease;
            pointer-events: none;
            opacity: 0;
        }
    </style>
    @stack('head')
</head>
<body class="min-h-screen flex flex-col bg-surface antialiased font-sans">
    {{-- Top Loading Indicator Bar --}}
    <div id="nav-loading-bar"></div>

    {{-- Flash Messages --}}
    @if(session('application_submitted'))
        <div class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-navy/45 backdrop-blur-sm" x-data="{ show: true }" x-show="show" x-transition.opacity>
            <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white border border-emerald-100 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="application-success-title">
                <div class="h-2 bg-gradient-to-r from-emerald-500 via-teal-400 to-blue-500"></div>
                <div class="p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Application submitted</p>
                                <h2 id="application-success-title" class="mt-1 text-xl font-heading font-extrabold text-navy">Your application is in</h2>
                            </div>
                        </div>
                        <button type="button" @click="show = false" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-navy" aria-label="Close confirmation">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <p class="mt-5 text-sm leading-6 text-slate-600">Your application for <strong class="text-navy">{{ session('application_submitted.job_title') }}</strong> was sent successfully.</p>
                    <div class="mt-5 flex items-center justify-between gap-3 rounded-2xl bg-slate-50 p-4 border border-slate-200">
                        <span class="text-xs font-semibold text-slate-500">AI match score</span>
                        <strong class="text-2xl font-heading font-extrabold text-emerald-600">{{ session('application_submitted.score') }}%</strong>
                    </div>
                    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" @click="show = false" class="btn btn-outline w-full sm:w-auto text-xs font-bold">Continue browsing</button>
                        <a href="{{ route('seeker.dashboard', ['tab' => 'applications']) }}" class="btn btn-primary w-full sm:w-auto text-center text-xs font-bold">View my applications</a>
                    </div>
                </div>
            </div>
        </div>
    @elseif(session('success'))
        <div class="fixed top-4 right-4 z-[100] animate-slide-down" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
            <x-ui.alert type="success" dismissible>{{ session('success') }}</x-ui.alert>
        </div>
    @endif
    @if(session('error'))
        <div class="fixed top-4 right-4 z-[100] animate-slide-down" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition>
            <x-ui.alert type="danger" dismissible>{{ session('error') }}</x-ui.alert>
        </div>
    @endif

    {{-- Header. Hidden on focused pages like sign-in, where the whole site nav
         is a distraction from the one thing the visitor came to do. --}}
    @unless($bare)
        <x-public-header />
    @endunless

    {{-- Page Content with Smooth Animated Transition --}}
    <main class="flex-1 page-transition-wrap">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    @unless($bare)
        <x-footer />
    @endunless

    {{-- Global AI Recruitment Copilot Drawer --}}
    @unless($bare)
        <x-ai-chat-drawer />
    @endunless

    {{-- Instant Hover Preload & Transition Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bar = document.getElementById('nav-loading-bar');
            const preloaded = new Set();

            function preload(url) {
                if (!url || preloaded.has(url) || url.startsWith('#') || url.startsWith('javascript:')) return;
                try {
                    const parsed = new URL(url, window.location.origin);
                    if (parsed.origin !== window.location.origin) return;
                    preloaded.add(url);
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = url;
                    document.head.appendChild(link);
                } catch(e) {}
            }

            document.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
                    a.addEventListener('mouseenter', () => preload(href), { passive: true });
                    a.addEventListener('touchstart', () => preload(href), { passive: true });
                    a.addEventListener('click', function(e) {
                        if (e.metaKey || e.ctrlKey || e.shiftKey || a.target === '_blank') return;
                        if (bar) {
                            bar.style.opacity = '1';
                            bar.style.width = '70%';
                        }
                    });
                }
            });

            window.addEventListener('pageshow', () => {
                if (bar) {
                    bar.style.width = '100%';
                    setTimeout(() => {
                        bar.style.opacity = '0';
                        bar.style.width = '0%';
                    }, 200);
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>