{{-- Every attribute this layout reads must be declared here.
     Declaring @props at all changes the rules: undeclared attributes stop
     being extracted as variables and go to $attributes instead. Adding only
     `bare` silently emptied $title and $description, so every page on the site
     fell through to the same site-wide SEO title — identical titles on the
     homepage, every vacancy and every blog post, which is as bad for search as
     having none. --}}
@props([
    'bare' => false,
    // Separate from `bare`: a page can want the header and none of the footer.
    // The Lucky AI conversation is the case — TickBig's chat has no footer at
    // all, it just extends as the conversation grows, and a marketing footer
    // under a half-finished conversation reads as the end of the page.
    'footer' => true,
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Newsreader:ital,opsz,wght@0,6..72,400..800;1,6..72,400..800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /*
            Palette and type, set as token overrides rather than by rebuilding
            the stylesheet. This project ships a prebuilt CSS bundle with no Node
            step, so the tokens the bundle already defines are the only sane
            place to change the look — every page picks these up at once, and
            nothing has to be recompiled.

            Two changes, both asked for after looking at how tickbig.com reads:

            1. Inter for UI text. Their pages use it, and it is the reason they
               look cleaner at small sizes than we did. It is also free and
               open source — ChatGPT's own typefaces (Söhne, OpenAI Sans) are
               licensed and we cannot ship them. Newsreader stays for display
               headings; it is the one thing in our identity they do not have.

            2. The greys move off slate and onto the navy in our logo. The page
               ground is a soft blue-white rather than a neutral grey, and cards
               stay pure white on top of it, which is what makes white surfaces
               read as deliberate instead of as an unstyled background.

            Muted text was also darkened from #94a3b8: against a light ground it
            sat near 2.6:1, which is unreadable outdoors — and our candidates are
            on site, in sunlight, on cheap phones.
        */
        :root {
            --font-sans: "Inter", ui-sans-serif, system-ui, -apple-system, sans-serif;
            --font-heading: "Inter", ui-sans-serif, system-ui, sans-serif;

            --color-surface: #F2F6FC;
            --color-surface-raised: #FFFFFF;
            --color-surface-sunken: #E8EFF9;

            --color-border: #DCE6F3;

            --color-text-primary: #0B1E38;
            --color-text-secondary: #46586F;
            --color-text-muted: #6E829C;
        }

        /* Alpine hides these once it boots; without the rule every x-show="false"
           panel is painted first and then yanked away, so a multi-step form
           flashes all of its steps at once on load. */
        [x-cloak] { display: none !important; }

        @keyframes pageEntrance {
            0% { opacity: 0; transform: translateY(6px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .page-transition-wrap {
            animation: pageEntrance 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        /* Centred loader shown while a page is being fetched. Plain CSS: the
           Tailwind bundle is prebuilt with no Node step, so a utility invented
           here would not exist at runtime. */
        #lb-loader {
            position: fixed;
            inset: 0;
            z-index: 90;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(242, 246, 252, 0.72);
            backdrop-filter: blur(2px);
        }
        #lb-loader.is-on { display: flex; }
        #lb-loader .lb-spin {
            width: 38px;
            height: 38px;
            border-radius: 9999px;
            border: 3px solid rgba(3, 31, 73, 0.12);
            border-top-color: #18A66A;
            animation: lb-spin 0.7s linear infinite;
        }
        @keyframes lb-spin { to { transform: rotate(360deg); } }

        /* The incoming page slides in from the right rather than snapping. */
        @keyframes lb-page-in {
            from { opacity: 0; transform: translateX(14px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        main.lb-swapped { animation: lb-page-in 0.22s cubic-bezier(0.16, 1, 0.3, 1); }

        /* Deliberately NOT `html { scroll-behavior: smooth }`. Setting it
           globally swallows programmatic scrolling: with the rule in place,
           window.scrollTo does nothing at all — even with behavior:'auto' —
           so anchor navigation silently landed at the top of the page. Smooth
           scrolling is done explicitly in JS below instead, where it can be
           measured and cannot break anything else. */

        @media (prefers-reduced-motion: reduce) {
            #lb-loader .lb-spin { animation-duration: 2s; }
            main.lb-swapped { animation: none; }
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
{{-- `data-layout` is read by the soft navigation below: a page belonging to
     a different layout must get a real browser load, never a <main> swap.
     See the guard in navigate(). --}}
<body data-layout="app" class="min-h-screen flex flex-col bg-surface antialiased font-sans">
    {{-- Top Loading Indicator Bar --}}
    <div id="nav-loading-bar"></div>

    {{-- Centred loader. Sits under the header's z-index on purpose: the top bar
         stays visible and interactive while the page underneath changes. --}}
    <div id="lb-loader" aria-hidden="true"><span class="lb-spin"></span></div>

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
    @if(! $bare && $footer)
        <x-footer />
    @endif

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

    {{-- Soft navigation. See the comment block in the deploy note: the header
         is never re-rendered, so the logo cannot flash and the account menu
         keeps its state. Anything uncertain falls back to a real navigation. --}}
    <script>
    (function () {
        var loader = document.getElementById('lb-loader');
        var busy = false;

        // Without these the page still works exactly as before — this whole
        // file is an enhancement, never a requirement.
        if (!window.fetch || !window.history.pushState || !window.DOMParser) return;

        // The top progress line belongs to an older script that only ever
        // finished it on `pageshow` — an event a soft navigation never fires.
        // So every soft click left it parked at 70% for good: the "loading line
        // stuck at three quarters" sir reported. Soft nav now drives it too.
        var progress = document.getElementById('nav-loading-bar');

        function show() {
            if (loader) loader.classList.add('is-on');
            if (progress) {
                progress.style.opacity = '1';
                progress.style.width = '70%';
            }
        }

        function hide() {
            if (loader) loader.classList.remove('is-on');
            if (!progress) return;

            progress.style.width = '100%';
            window.setTimeout(function () {
                progress.style.opacity = '0';
                progress.style.width = '0%';
            }, 200);
        }

        function hardNav(url) { window.location.href = url; }

        /**
         * Glide to an anchor, 80px clear of the sticky header.
         *
         * Animated by hand rather than with behavior:'smooth'. Native smooth
         * scrolling is unreliable here — it is skipped outright in some
         * contexts — and a section that silently does not scroll is exactly the
         * bug this replaced.
         */
        /**
         * The prebuilt CSS bundle sets `scroll-behavior: smooth` on <html>, and
         * that bundle cannot be rebuilt (no Node step). While it is in force,
         * window.scrollTo is simply ignored — every programmatic scroll on this
         * site silently does nothing. An inline style on the element is the only
         * thing that outranks it, so every scroll here is wrapped in one.
         */
        function instantly(fn) {
            var de = document.documentElement;
            var prev = de.style.scrollBehavior;
            de.style.scrollBehavior = 'auto';
            try { fn(); } finally {
                window.setTimeout(function () { de.style.scrollBehavior = prev; }, 0);
            }
        }

        function toTop() { instantly(function () { window.scrollTo(0, 0); }); }

        /**
         * Scroll to a fragment once the page has stopped moving.
         *
         * Retries for up to a second: after a swap the entry animation is still
         * running, re-run scripts are still mutating the DOM and images are
         * still loading, so the anchor's position keeps changing. It stops as
         * soon as two consecutive measurements agree.
         */
        function honourHash(hash) {
            var tries = 0;
            var lastTop = null;

            function attempt() {
                var el = hash ? document.querySelector(hash) : null;
                if (!el) { if (tries++ < 20) window.setTimeout(attempt, 50); else toTop(); return; }

                var top = Math.round(el.getBoundingClientRect().top + window.scrollY);

                if (lastTop !== null && Math.abs(top - lastTop) < 2) { scrollToAnchor(el); return; }

                lastTop = top;
                if (tries++ < 20) window.setTimeout(attempt, 50);
                else scrollToAnchor(el);
            }

            attempt();
        }

        // A fragment in the address bar on a cold load gets the same treatment:
        // the browser's own jump happens before images have sized the page, so
        // it habitually lands short.
        if (window.location.hash) {
            window.addEventListener('load', function () { honourHash(window.location.hash); });
        }

        function scrollToAnchor(el) {
            var target = el.getBoundingClientRect().top + window.scrollY - 80;
            if (target < 0) target = 0;

            var start = window.scrollY;
            var distance = target - start;
            if (Math.abs(distance) < 2) return;

            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduce) { instantly(function () { window.scrollTo(0, target); }); return; }

            // Animated by hand, one frame at a time, with the CSS smoothing held
            // off for the whole run rather than per frame.
            var de = document.documentElement;
            var prev = de.style.scrollBehavior;
            de.style.scrollBehavior = 'auto';

            // Stepped on a timer, not requestAnimationFrame. rAF is paused
            // outright in a background or unfocused tab, so an rAF-driven scroll
            // does not run late — it never runs at all, and the page silently
            // stays where it was. A timer is marginally less smooth and always
            // finishes.
            var duration = 420;
            var began = (window.performance && performance.now) ? performance.now() : Date.now();

            function step() {
                var now = (window.performance && performance.now) ? performance.now() : Date.now();
                var t = Math.min((now - began) / duration, 1);
                var eased = t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
                window.scrollTo(0, start + distance * eased);
                if (t < 1) { window.setTimeout(step, 16); }
                else {
                    de.style.scrollBehavior = prev;
                    // Tell the scroll-spy we moved. A programmatic scroll does
                    // not always deliver a scroll event, which left the active
                    // pill sitting on whatever it was before the jump.
                    window.dispatchEvent(new Event('scroll'));
                }
            }

            step();
        }

        function swap(el, next) {
            if (!el || !next) return;
            el.replaceWith(next);
        }

        /**
         * Re-run any <script> the incoming page carried. Nodes inserted through
         * innerHTML never execute, so without this the agent home's rolling
         * line would silently stop rolling after one soft navigation.
         */
        function runScripts(root) {
            root.querySelectorAll('script').forEach(function (old) {
                var s = document.createElement('script');
                for (var i = 0; i < old.attributes.length; i++) {
                    s.setAttribute(old.attributes[i].name, old.attributes[i].value);
                }
                s.textContent = old.textContent;
                old.replaceWith(s);
            });
        }

        function navigate(url, push, init) {
            if (busy) return;
            busy = true;
            show();

            // Kept separately: fetch() never reports a fragment back, because a
            // fragment is never sent to the server.
            var wantedHash = '';
            try { wantedHash = new URL(url, location.href).hash; } catch (err) {}

            /*
                Where a fallback should actually land.

                `url` is what we asked for; after a POST that is the form's
                action, and the server has almost certainly redirected somewhere
                else by the time we decide we cannot swap. Falling back to `url`
                sent a successful admin sign-in back to /login as a GET — logged
                in, but staring at the sign-in form as though nothing had
                happened. Once the response resolves, this holds where we really
                ended up.
            */
            var landing = url;

            fetch(url, init || { credentials: 'same-origin', headers: { 'X-Soft-Nav': '1' } })
                .then(function (res) {
                    if (!res.ok || res.redirected && new URL(res.url).origin !== location.origin) throw new Error('bad');
                    return res.text().then(function (html) { return { html: html, url: res.url }; });
                })
                .then(function (payload) {
                    landing = payload.url || url;
                    var doc = new DOMParser().parseFromString(payload.html, 'text/html');
                    var nextMain = doc.querySelector('main');
                    var currentMain = document.querySelector('main');

                    // A response we cannot recognise as one of our pages (a
                    // redirect to sign-in, an error page) gets a real load.
                    if (!nextMain || !currentMain) throw new Error('no main');

                    /*
                        A page from a different layout also gets a real load, and
                        this one is not theoretical.

                        The admin is the only area still on its own <html>, with
                        its own <aside>, its own top bar and its own Alpine root.
                        Signing in at /admin/login posts through this handler,
                        which followed the redirect to /admin, found a perfectly
                        good <main> in it — the check above passes — and swapped
                        that <main> into the *sign-in page's* body. The admin's
                        entire chrome was discarded: no rail, no header, no
                        x-data, and no way to reach another admin screen. A
                        refresh fixed it, which is what made it look intermittent.

                        Comparing the layout each document declares is enough,
                        and it catches every future case of this rather than
                        just the login one.
                    */
                    if ((doc.body && doc.body.dataset.layout) !== document.body.dataset.layout) {
                        throw new Error('layout change');
                    }

                    // Alpine has to be able to wake the new DOM up. If it is not
                    // there, a soft swap would leave every dropdown dead.
                    if (!window.Alpine || typeof window.Alpine.initTree !== 'function') throw new Error('no alpine');

                    document.title = doc.title || document.title;

                    // The header is left mounted wherever both pages have one —
                    // that is the whole point, it must not flash. But `bare`
                    // pages (sign in, register) have none, and leaving the old
                    // one behind put a second header above their own card.
                    // `body > header` on purpose: the site header is a direct
                    // child of body, while a bare page's own little header sits
                    // inside <main>. A loose 'header' selector matches that one
                    // too and would think the site header was still wanted.
                    var nextHeader = doc.querySelector('body > header');
                    var currentHeader = document.querySelector('body > header');

                    if (!nextHeader && currentHeader) {
                        currentHeader.remove();
                        currentHeader = null;
                    } else if (nextHeader && !currentHeader) {
                        document.body.insertBefore(nextHeader, document.body.firstChild);
                        if (window.Alpine && window.Alpine.initTree) window.Alpine.initTree(nextHeader);
                    }

                    // Both have one: keep it, and change only the pill nav.
                    var nextNav = doc.querySelector('body > header nav');
                    var currentNav = document.querySelector('body > header nav');
                    if (nextNav && currentNav) swap(currentNav, nextNav);

                    nextMain.classList.add('lb-swapped');
                    swap(currentMain, nextMain);

                    // The footer is present on most pages and absent on the
                    // conversation, so it is added or removed to match.
                    var nextFooter = doc.querySelector('footer');
                    var currentFooter = document.querySelector('footer');
                    if (nextFooter && currentFooter) { swap(currentFooter, nextFooter); }
                    else if (nextFooter && !currentFooter) { document.body.appendChild(nextFooter); }
                    else if (!nextFooter && currentFooter) { currentFooter.remove(); }

                    runScripts(document.querySelector('main'));
                    if (nextFooter) runScripts(document.querySelector('footer') || document.body);

                    window.Alpine.initTree(document.querySelector('main'));
                    if (currentNav !== nextNav && document.querySelector('body > header nav')) {
                        window.Alpine.initTree(document.querySelector('body > header nav'));
                    }

                    if (push) window.history.pushState({ softnav: true }, '', payload.url + wantedHash);

                    // A fragment means "take me to that part of the page", so a
                    // jump to the top would be the one thing the click did not ask for.
                    // Handled by honourHash() rather than inline. Doing it in
                    // this chain proved unreliable — the swap, the entry
                    // animation and the re-run scripts all move the layout under
                    // it, and a single measurement taken here lands on the wrong
                    // number or is undone. The watcher retries until the page
                    // settles instead.
                    if (wantedHash) honourHash(wantedHash);
                    else toTop();

                    busy = false;
                    hide();
                })
                .catch(function () { hardNav(landing); });
        }

        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            var a = e.target.closest ? e.target.closest('a[href]') : null;
            if (!a) return;

            if (a.target && a.target !== '_self') return;
            if (a.hasAttribute('download') || a.hasAttribute('data-no-soft-nav')) return;

            var href = a.getAttribute('href') || '';
            if (!href || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;

            // A bare "#section" link. This used to return here and leave it to
            // the browser — which cannot scroll at all, because the prebuilt
            // bundle's `scroll-behavior: smooth` swallows it. That was why
            // Explore and Opportunities did nothing.
            if (href.charAt(0) === '#') {
                if (href === '#') return;
                var section = document.querySelector(href);
                if (!section) return;
                e.preventDefault();
                scrollToAnchor(section);
                window.history.pushState({ softnav: true }, '', href);
                return;
            }

            var target;
            try { target = new URL(href, location.href); } catch (err) { return; }

            if (target.origin !== location.origin) return;
            // An anchor on the page we are already on: scroll to it ourselves.
            // Left to the browser this is an instant jump, which is the jolt sir
            // described when clicking Explore.
            if (target.pathname === location.pathname && target.search === location.search && target.hash) {
                var here = document.querySelector(target.hash);
                if (!here) return;
                e.preventDefault();
                scrollToAnchor(here);
                window.history.pushState({ softnav: true }, '', target.href);
                return;
            }

            e.preventDefault();
            navigate(target.href, true);
        });

        document.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return;

            var form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.hasAttribute('data-no-soft-nav')) return;
            if (form.target && form.target !== '_self') return;

            var method = (form.getAttribute('method') || 'get').toLowerCase();
            var action = form.getAttribute('action') || location.href;

            var url;
            try { url = new URL(action, location.href); } catch (err) { return; }
            if (url.origin !== location.origin) return;

            // GET forms are just a navigation with a query string.
            if (method !== 'post') {
                e.preventDefault();
                var params = new URLSearchParams(new FormData(form));
                url.search = params.toString();
                navigate(url.href, true);
                return;
            }

            e.preventDefault();

            // FormData carries the CSRF token and any _method spoof, and handles
            // file inputs, so the resume upload goes through this path too.
            //
            // The submitter matters: `new FormData(form)` does NOT include the
            // name/value of the button that was pressed. Every answer chip in
            // the Lucky AI conversation is a <button name="answer" value="...">,
            // so without this the chat posts an empty answer and the question
            // just re-renders. It was masked at first because the opening
            // confirm falls back to "Yes" when no answer arrives.
            var body;
            try {
                body = new FormData(form, e.submitter || null);
            } catch (err) {
                body = new FormData(form);
            }

            // Older engines ignore the second argument, so add it by hand when
            // it did not come through.
            if (e.submitter && e.submitter.name && !body.has(e.submitter.name)) {
                body.append(e.submitter.name, e.submitter.value);
            }

            navigate(url.href, true, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: { 'X-Soft-Nav': '1' }
            });
        });

        window.addEventListener('popstate', function (e) {
            if (e.state && e.state.softnav) navigate(location.href, false);
        });

        // Mark the first page so Back to it is handled the same way.
        window.history.replaceState({ softnav: true }, '', location.href);
    })();
    </script>

    {{-- Scroll-spy for the pill nav. Explore and Opportunities scroll rather
         than navigate, so without this the server's "Home" stays lit however far
         down the page you are. --}}
    <script>
    (function () {
        // These two strings must match what the header renders, or the pill the
        // server lit and the pill this lights will look different.
        var ON  = ['bg-navy', 'text-white', 'shadow-xs', 'font-bold'];
        var OFF = ['font-semibold', 'text-slate-700', 'hover:text-navy', 'hover:bg-slate-200/70'];

        function paint(links, activeHref) {
            links.forEach(function (a) {
                var isOn = a.getAttribute('href') === activeHref;
                ON.forEach(function (c) { a.classList.toggle(c, isOn); });
                OFF.forEach(function (c) { a.classList.toggle(c, !isOn); });
            });
        }

        var ticking = false;

        function update() {
            ticking = false;

            var browse = document.getElementById('browse');
            var opportunities = document.getElementById('opportunities');
            if (!browse && !opportunities) return;   // not a page with these sections

            // Re-queried every time: soft navigation replaces the <nav> element.
            var nav = document.querySelector('header nav');
            if (!nav) return;

            // The Home link renders as an absolute URL with no trailing slash
            // ("http://host"), so it has to be resolved rather than string-matched.
            var links = Array.prototype.filter.call(nav.querySelectorAll('a[href]'), function (a) {
                var h = a.getAttribute('href') || '';
                if (h === '#browse' || h === '#opportunities') return true;
                try {
                    var u = new URL(h, location.href);
                    return u.origin === location.origin && (u.pathname === '/' || u.pathname === '') && !u.hash;
                } catch (e) { return false; }
            });
            if (links.length < 2) return;   // the signed-in nav has no anchors — leave it alone

            // A third of the way down the viewport: a section counts as current
            // once it is properly on screen, not the instant its top edge appears.
            var line = window.scrollY + window.innerHeight / 3;
            var active = links[0].getAttribute('href');

            // Document offsets, not offsetTop: both markers sit inside <main>,
            // which is their offsetParent, so offsetTop is short by the header's
            // height and every section would switch that much too early.
            function docTop(el) { return el.getBoundingClientRect().top + window.scrollY; }

            if (browse && line >= docTop(browse)) active = '#browse';
            if (opportunities && line >= docTop(opportunities)) active = '#opportunities';

            paint(links, active);
        }

        function onScroll() {
            if (ticking) return;
            ticking = true;
            // setTimeout, not requestAnimationFrame: rAF is paused in an
            // unfocused tab, which would leave the active pill frozen wherever
            // it was when focus was lost.
            window.setTimeout(update, 60);
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        window.addEventListener('popstate', onScroll);
        document.addEventListener('DOMContentLoaded', update);
        update();
    })();
    </script>

    @stack('scripts')
</body>
</html>