<x-layouts.app title="Luckyboss Employment Agency Pte. Ltd | AI-Powered Recruitment Platform">
{{-- Motion and card craft for the front page.

     Written as plain CSS rather than utility classes: this project ships a
     prebuilt Tailwind bundle with no Node step, so a class invented here would
     simply not exist at runtime. Everything below is therefore self-contained.

     The reveal uses IntersectionObserver directly instead of the Alpine plugin,
     so a section can never end up permanently invisible because a plugin failed
     to load — without JS at all, `.lb-reveal` is already in its final state and
     the page just appears with no animation. --}}
<style>
    @keyframes lb-rise {
        from { opacity: 0; transform: translateY(18px); }
        to   { opacity: 1; transform: none; }
    }

    /* Hero: a short staged entrance, not a carnival. */
    .lb-enter { animation: lb-rise .6s cubic-bezier(.22,.61,.36,1) both; }
    .lb-d1 { animation-delay: .05s; }
    .lb-d2 { animation-delay: .13s; }
    .lb-d3 { animation-delay: .21s; }
    .lb-d4 { animation-delay: .29s; }

    /* Scroll reveal. Visible by default; JS hides then releases it, so the
       content is never lost when scripting is off or an observer fails. */
    .js-reveal .lb-reveal { opacity: 0; transform: translateY(22px); }
    .js-reveal .lb-reveal.lb-in {
        opacity: 1; transform: none;
        transition: opacity .65s cubic-bezier(.22,.61,.36,1),
                    transform .65s cubic-bezier(.22,.61,.36,1);
    }
    .js-reveal .lb-reveal:nth-child(2) { transition-delay: .06s; }
    .js-reveal .lb-reveal:nth-child(3) { transition-delay: .12s; }
    .js-reveal .lb-reveal:nth-child(4) { transition-delay: .18s; }
    .js-reveal .lb-reveal:nth-child(5) { transition-delay: .24s; }
    .js-reveal .lb-reveal:nth-child(6) { transition-delay: .30s; }
    .js-reveal .lb-reveal:nth-child(7) { transition-delay: .36s; }
    .js-reveal .lb-reveal:nth-child(8) { transition-delay: .42s; }

    /* One hover language for every card on the page. */
    .lb-card {
        position: relative;
        background: #fff;
        border: 1px solid #E4EAF2;
        border-radius: 18px;
        transition: transform .3s cubic-bezier(.22,.61,.36,1),
                    box-shadow .3s cubic-bezier(.22,.61,.36,1),
                    border-color .3s ease;
    }
    .lb-card:hover {
        transform: translateY(-4px);
        border-color: #C9E6D8;
        box-shadow: 0 18px 40px -24px rgba(3,31,73,.45);
    }

    /* The accent hairline that draws itself across the top on hover. */
    .lb-card::after {
        content: ""; position: absolute; left: 18px; right: 18px; top: -1px;
        height: 2px; border-radius: 2px;
        background: linear-gradient(90deg, #18A66A, #2563EB);
        transform: scaleX(0); transform-origin: left;
        transition: transform .45s cubic-bezier(.22,.61,.36,1);
    }
    .lb-card:hover::after { transform: scaleX(1); }

    /* The icon tile. Colour deepens rather than the whole card lighting up. */
    .lb-tile {
        width: 46px; height: 46px; border-radius: 13px;
        display: flex; align-items: center; justify-content: center;
        background: #EAF6F0; color: #18A66A;
        transition: background .3s ease, color .3s ease, transform .35s cubic-bezier(.34,1.56,.64,1);
    }
    .lb-card:hover .lb-tile { background: #18A66A; color: #fff; transform: rotate(-6deg) scale(1.06); }

    /* The photograph lifts with the card rather than sitting still inside it. */
    .lb-shot { transition: transform .7s cubic-bezier(.22,.61,.36,1); }
    .lb-card:hover .lb-shot { transform: scale(1.07); }

    /* The scroll cue at the foot of the hero. A slow drift, not a bounce. */
    @keyframes lb-drift { 0%,100% { transform: translateY(0); } 50% { transform: translateY(7px); } }
    .lb-scroll-cue svg { animation: lb-drift 2.4s ease-in-out infinite; }
    .lb-scroll-cue:hover svg { animation-duration: 1.1s; }

    /* The rolling placeholder. It swaps text at the midpoint of the fade so
       the change is never visible as a jump. */
    @keyframes lb-roll {
        0%, 8%    { opacity: 0; transform: translateY(6px); }
        18%, 82%  { opacity: 1; transform: none; }
        92%, 100% { opacity: 0; transform: translateY(-6px); }
    }
    .lb-ghost {
        position: absolute; left: 48px; top: 50%; transform: translateY(-50%);
        pointer-events: none; font-size: 15px; color: #9AA8BA;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        max-width: calc(100% - 64px);
    }
    .lb-ghost span { display: inline-block; animation: lb-roll 3s ease-in-out infinite; }

    .lb-arrow { transition: transform .3s cubic-bezier(.22,.61,.36,1); }
    .lb-card:hover .lb-arrow { transform: translateX(5px); }

    .lb-count {
        font-size: 11px; font-weight: 700; letter-spacing: .04em;
        padding: 3px 9px; border-radius: 999px;
        background: #F2F7FF; color: #2563EB; border: 1px solid #E1ECFB;
        transition: background .3s ease, color .3s ease, border-color .3s ease;
    }
    .lb-card:hover .lb-count { background: #031F49; color: #fff; border-color: #031F49; }

    /* Editorial cards. Text-led on purpose — a coloured spine reads as a
       decision, where a placeholder image reads as a missing one. */
    .lb-post { overflow: hidden; }
    .lb-post .lb-spine {
        height: 4px; width: 100%;
        background: linear-gradient(90deg, #18A66A 0%, #2563EB 100%);
        transform: scaleX(.28); transform-origin: left;
        transition: transform .5s cubic-bezier(.22,.61,.36,1);
    }
    .lb-post:hover .lb-spine { transform: scaleX(1); }
    .lb-post h3 { transition: color .25s ease; }
    .lb-post:hover h3 { color: #18A66A; }

    /* Anyone who has asked their system to calm down gets no motion at all. */
    @media (prefers-reduced-motion: reduce) {
        .lb-enter { animation: none; }
        .js-reveal .lb-reveal { opacity: 1; transform: none; transition: none; }
        .lb-card, .lb-card::after, .lb-tile, .lb-arrow, .lb-count,
        .lb-shot, .lb-post .lb-spine, .lb-post h3 { transition: none; }
        .lb-scroll-cue svg, .lb-ghost span { animation: none; }
        .lb-card:hover { transform: none; }
    }
</style>

<script>
    // Marking the document here rather than in the stylesheet is what makes the
    // no-JS case safe: until this runs, .lb-reveal has no hidden state at all.
    document.documentElement.classList.add('js-reveal');

    (function () {
        var started = false;

        function reveal(el) { el.classList.add('lb-in'); }

        function start() {
            if (started) return;
            started = true;

            var items = document.querySelectorAll('.lb-reveal');

            if (!('IntersectionObserver' in window)) {
                items.forEach(reveal);
                return;
            }

            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    reveal(entry.target);
                    io.unobserve(entry.target);
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

            items.forEach(function (el) { io.observe(el); });
        }

        // Three ways in, because one was not enough. The first attempt hung
        // this listener on DOMContentLoaded alone; something else on the page
        // meant it never ran, and every card below the fold stayed at opacity
        // zero — content invisible, which is far worse than content that simply
        // does not animate.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', start);
        } else {
            start();
        }
        window.addEventListener('load', start);

        // The guarantee. Whatever happens above, nothing stays hidden: after
        // three seconds everything is shown regardless.
        setTimeout(function () {
            document.querySelectorAll('.lb-reveal:not(.lb-in)').forEach(reveal);
        }, 3000);

        // Rolling search suggestions, taken from the trades actually in the
        // catalogue rather than a hardcoded list, so it can never advertise a
        // sector with nothing in it.
        var ghost = document.querySelector('[data-ghost]');
        var input = document.querySelector('[data-rolling-placeholder]');

        if (ghost && input) {
            var terms = @json($rollingTerms ?? []);

            if (terms.length) {
                var i = 0;
                var inner = ghost.querySelector('span');

                setInterval(function () {
                    if (input.value) return;              // never fight a typist
                    i = (i + 1) % terms.length;
                    inner.textContent = terms[i];
                }, 3000);

                // Restarting the animation on each swap keeps text and fade in
                // step; without it they drift apart within a minute.
                inner.addEventListener('animationiteration', function () {});
            }

            var hide = function () { ghost.style.display = input.value ? 'none' : ''; };
            input.addEventListener('input', hide);
            input.addEventListener('focus', function () { ghost.style.opacity = '.5'; });
            input.addEventListener('blur', function () { ghost.style.opacity = ''; hide(); });
        }
    })();
</script>

    {{-- ═══════════════════════════════════════════════════════════
         1. HERO — ONE FULL SCREEN

         Reordered after Shantosh described what it should feel like: the
         question at the top, the search in the middle of the screen, the four
         doors beneath it, and "See the jobs" pinned to the very bottom edge so
         it is the last thing you reach and scrolling actually goes somewhere.

         The section is exactly one viewport tall for that reason. Before this
         the cue sat halfway down with empty space under it, which made the page
         look like it had not finished loading rather than like it was inviting
         you to scroll.
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="relative flex flex-col"
             style="background:#F7F9FC; min-height:calc(100vh - 80px);">

        <div class="container mx-auto px-6 max-w-4xl flex-1 flex flex-col justify-center text-center py-10">

            <p class="lb-enter text-xs font-bold uppercase tracking-[0.16em] mb-4" style="color:#18A66A;">
                Singapore &middot; Malaysia &middot; India
            </p>

            <h1 class="lb-enter lb-d1 font-heading font-bold tracking-tight leading-[1.08] mb-3 text-[34px] sm:text-[48px] lg:text-[56px]"
                style="color:#031F49;">
                What brings you here today?
            </h1>

            <p class="lb-enter lb-d2 text-base sm:text-lg mb-9 max-w-xl mx-auto" style="color:#5A6C82;">
                Pick one and we will take you straight to it.
            </p>

            {{-- The search sits at the centre of the screen because it is the
                 thing a returning candidate came for. The placeholder cycles
                 through real trades from our own catalogue rather than sitting
                 static — the job seeker app does the same on its search bar,
                 and it is what tells somebody what they are allowed to type. --}}
            <form action="{{ route('jobs.index') }}" method="GET"
                  class="lb-enter lb-d3 flex items-stretch gap-2 max-w-2xl mx-auto w-full mb-8">
                <label for="keyword" class="sr-only">Search jobs</label>
                <div class="relative flex-1">
                    <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"
                         fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="color:#9AA8BA;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input id="keyword" name="keyword" type="search" autocomplete="off"
                           value="{{ request('keyword') }}"
                           data-rolling-placeholder
                           placeholder=""
                           class="w-full rounded-2xl pl-12 pr-4 py-4 text-[15px] outline-none transition-all"
                           style="border:1px solid #E4EAF2;background:#fff;color:#031F49;"
                           onfocus="this.style.borderColor='#18A66A';this.style.boxShadow='0 0 0 3px rgba(24,166,106,.14)'"
                           onblur="this.style.borderColor='#E4EAF2';this.style.boxShadow='none'">
                    {{-- A real element rather than the placeholder attribute:
                         a placeholder cannot be animated, and swapping its text
                         on a timer reads as a glitch. This fades out, changes,
                         and fades back in, and it disappears the moment anyone
                         types. --}}
                    <span class="lb-ghost" data-ghost aria-hidden="true"><span>Warehouse Supervisor</span></span>
                </div>
                <button type="submit" class="rounded-2xl px-7 font-bold text-[15px] transition-all"
                        style="background:#031F49;color:#fff;"
                        onmouseover="this.style.background='#052a63'"
                        onmouseout="this.style.background='#031F49'">
                    Search
                </button>
            </form>

            @php
                $doors = [
                    [
                        'label' => 'Find a job',
                        'note'  => $stats['activeJobs'] . ' open now',
                        'href'  => route('jobs.index'),
                        'icon'  => 'M20.25 14.15v4.073a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a12.06 12.06 0 0 1-6.596 0l-1.32-.377a2.25 2.25 0 0 1-1.632-2.163V14.15M3.75 8.25v10.5a2.25 2.25 0 0 0 2.25 2.25h12a2.25 2.25 0 0 0 2.25-2.25V8.25M3.75 8.25h16.5M9 5.25V4.5A1.5 1.5 0 0 1 10.5 3h3a1.5 1.5 0 0 1 1.5 1.5v.75',
                    ],
                    [
                        'label' => 'Hire workers',
                        'note'  => 'Post a vacancy',
                        'href'  => route('register.employer'),
                        'icon'  => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                    ],
                    [
                        'label' => 'Browse by trade',
                        'note'  => 'Construction, driving, care',
                        'href'  => route('categories.index'),
                        'icon'  => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085',
                    ],
                    [
                        'label' => 'I have an account',
                        'note'  => 'Sign in',
                        'href'  => route('login'),
                        'icon'  => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25',
                    ],
                ];
            @endphp

            <div class="lb-enter lb-d4 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-7">
                @foreach($doors as $door)
                    <a href="{{ $door['href'] }}" class="lb-card px-4 py-5 sm:py-6 text-left">
                        <span class="lb-tile mb-3.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $door['icon'] }}"/>
                            </svg>
                        </span>
                        <span class="block font-bold text-[15px] sm:text-base leading-snug" style="color:#031F49;">
                            {{ $door['label'] }}
                        </span>
                        <span class="block text-xs sm:text-[13px] mt-1" style="color:#8494A8;">
                            {{ $door['note'] }}
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Counted from the database. The sign-in page used to advertise
                 5,000 jobs against a table holding 14. --}}
            <p class="lb-enter lb-d4 text-sm" style="color:#8494A8;">
                <strong style="color:#031F49;">{{ number_format($stats['activeJobs']) }}</strong>
                {{ Str::plural('live vacancy', $stats['activeJobs']) }}
                &middot;
                <strong style="color:#031F49;">{{ number_format($stats['employers']) }}</strong>
                verified {{ Str::plural('employer', $stats['employers']) }}
            </p>
        </div>

        {{-- Pinned to the bottom edge of the screen, so it is the last thing
             you reach and scrolling from it lands on the next full section. --}}
        <div class="pb-8 text-center">
            <a href="#browse" class="lb-scroll-cue inline-flex flex-col items-center gap-1 text-sm font-semibold"
               style="color:#5A6C82;">
                See the jobs
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                </svg>
            </a>
        </div>
    </section>

    <span id="browse"></span>

    {{-- ═══════════════════════════════════════════════════════════
         2. EXPLORE JOBS BY CATEGORY (With Real Cover Imagery)
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="py-20 lg:py-28 bg-white border-t border-border" x-data="{ visible: false }" x-intersect.threshold.15="visible = true">
        <div class="container mx-auto px-6 transition-all duration-700 transform" :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
            {{-- Section Header --}}
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span class="text-xs font-bold uppercase tracking-widest text-secondary-600 mb-2.5 block">Industry Sectors</span>
                <h2 class="text-3xl sm:text-4xl font-heading font-extrabold text-navy mb-3 tracking-tight">
                    Explore Jobs by <span class="text-secondary-500">Category</span>
                </h2>
                <p class="text-text-secondary text-base">
                    Discover targeted openings tailored to your industry expertise and professional background.
                </p>
            </div>

            @php
                // A photograph per trade, and an icon behind it. The picture
                // does the explaining for candidates who do not read long
                // English comfortably; the icon is what survives if the picture
                // never arrives.
                $categoryImages = [
                    'construction'  => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=600&q=80',
                    'manufacturing' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=600&q=80',
                    'warehouse'     => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=600&q=80',
                    'healthcare'    => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=600&q=80',
                    'health'        => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=600&q=80',
                    'logistics'     => 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=600&q=80',
                    'driving'       => 'https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=600&q=80',
                    'hospitality'   => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=600&q=80',
                    'domestic'      => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&w=600&q=80',
                    'engineering'   => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80',
                    'engineer'      => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80',
                    'sales'         => 'https://images.unsplash.com/photo-1556740738-b6a63e27c4df?auto=format&fit=crop&w=600&q=80',
                ];

                $defaultImage = 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=600&q=80';

                $categoryIcons = [
                    'construction'  => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085',
                    'manufacturing' => 'M42 42M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
                    'warehouse'     => 'M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z',
                    'healthcare'    => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z',
                    'health'        => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z',
                    'logistics'     => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
                    'driving'       => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25',
                    'hospitality'   => 'M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12.75 6.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
                    'domestic'      => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
                    'engineering'   => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63',
                    'engineer'      => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63',
                    'sales'         => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                ];

                // A trade with no mark of its own gets a generic one rather
                // than a broken image.
                $defaultIcon = 'M20.25 14.15v4.073a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a12.06 12.06 0 0 1-6.596 0l-1.32-.377a2.25 2.25 0 0 1-1.632-2.163V14.15M3.75 8.25v10.5a2.25 2.25 0 0 0 2.25 2.25h12a2.25 2.25 0 0 0 2.25-2.25V8.25M3.75 8.25h16.5M9 5.25V4.5A1.5 1.5 0 0 1 10.5 3h3a1.5 1.5 0 0 1 1.5 1.5v.75';
            @endphp

            {{-- Category Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @forelse($categories as $category)
                    @php 
                        $slug = Str::slug($category->name);
                        $matchedIcon = $defaultIcon;
                        $matchedImage = $defaultImage;
                        foreach($categoryIcons as $key => $ic) {
                            if(Str::contains($slug, $key) || Str::contains(strtolower($category->name), $key)) {
                                $matchedIcon = $ic;
                                $matchedImage = $categoryImages[$key] ?? $defaultImage;
                                break;
                            }
                        }
                        $jobCount = $category->jobs_count ?? ($category->jobs ? $category->jobs->count() : 0);
                    @endphp
                    <a href="{{ route('jobs.index', ['category' => $category->id]) }}"
                       class="lb-card lb-reveal group flex flex-col overflow-hidden">

                        {{-- The photograph is back.

                             It was removed to kill ten external requests, and
                             that traded the wrong thing away: many of the
                             candidates this platform is built for do not read
                             long English comfortably, and a picture of a
                             warehouse tells them what the card is faster than
                             the word "Warehouse" does. Sir asked for images and
                             he was right.

                             What stays fixed is the failure case: every image
                             is lazy-loaded, and if it does not arrive the tinted
                             ground and the trade icon underneath still read as
                             a finished card rather than a hole. --}}
                        <div class="relative overflow-hidden" style="height:150px;background:linear-gradient(135deg,#EAF1FA 0%,#E7F5EE 100%);">
                            <img src="{{ $matchedImage }}" alt="{{ $category->name }}" loading="lazy"
                                 class="lb-shot w-full h-full object-cover"
                                 onerror="this.style.display='none'">

                            <span class="lb-tile" style="position:absolute;left:14px;bottom:14px;background:rgba(255,255,255,.94);backdrop-filter:blur(6px);">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $matchedIcon }}"/>
                                </svg>
                            </span>

                            <span class="lb-count" style="position:absolute;right:14px;top:14px;background:rgba(255,255,255,.94);">
                                {{ number_format($jobCount) }} {{ Str::plural('job', $jobCount) }}
                            </span>
                        </div>

                        <div class="p-5 flex flex-col flex-1">
                            <h3 class="font-heading font-bold text-lg mb-1.5" style="color:#031F49;">
                                {{ $category->name }}
                            </h3>

                            <p class="text-[13px] leading-relaxed mb-4 flex-1" style="color:#7A8AA0;">
                                {{ $category->description ?? 'Verified openings from employers hiring across Singapore, Malaysia and India.' }}
                            </p>

                            <span class="inline-flex items-center gap-1.5 text-[13px] font-bold" style="color:#18A66A;">
                                Browse roles
                                <svg class="lb-arrow w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-12 text-text-muted">
                        No categories found.
                    </div>
                @endforelse
            </div>

            {{-- View All CTA --}}
            <div class="text-center mt-12">
                <a href="{{ route('categories.index') }}" class="btn btn-outline btn-md px-7 shadow-xs hover:shadow-md">
                    <span>View All Categories</span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         3. FEATURED OPPORTUNITIES (Rich Animated Cards with Skills & Perks)
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="py-24 lg:py-32 bg-[#f8fafc] border-y border-border" x-data="{ visible: false }" x-intersect.threshold.10="visible = true">
        <div class="container mx-auto px-6 transition-all duration-700 transform" :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
            {{-- Section Header --}}
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-14 gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 py-1 px-3.5 rounded-full bg-secondary-50 border border-secondary-200 text-xs font-bold text-secondary-700 uppercase tracking-widest mb-3">
                        <span class="w-2 h-2 rounded-full bg-secondary-500 animate-ping"></span>
                        <span>Direct Verified Vacancies</span>
                    </div>
                    <h2 class="text-3xl sm:text-5xl font-heading font-extrabold text-navy tracking-tight">
                        Featured <span class="text-secondary-500">Opportunities</span>
                    </h2>
                    <p class="text-text-secondary text-base sm:text-lg mt-2 max-w-2xl">
                        Hand-picked positions from actively hiring corporate employers with verified compensation packages.
                    </p>
                </div>
                <a href="{{ route('jobs.index') }}" class="btn btn-primary btn-md shrink-0 shadow-md hover:shadow-xl hover:scale-102 transition-all">
                    <span>Explore All 5,000+ Jobs</span>
                    <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>

            @php
                $jobProfiles = [
                    'cloud' => [
                        'sector' => 'Cloud & DevOps Tech',
                        'icon' => '☁️',
                        'skills' => ['AWS / Cloud Arch', 'Docker & K8s', 'CI/CD Pipelines'],
                        'perk' => 'Hybrid Work + Tech Allowances',
                        'flag' => '🇮🇳 India, Bengaluru',
                    ],
                    'supply chain & logistics' => [
                        'sector' => 'Freight & Multi-Modal',
                        'icon' => '🚢',
                        'skills' => ['Logistics ERP', 'Dispatch Control', 'Vendor Operations'],
                        'perk' => 'PF + Annual Performance Bonus',
                        'flag' => '🇮🇳 India, Chennai',
                    ],
                    'civil infrastructure' => [
                        'sector' => 'Civil & Infrastructure',
                        'icon' => '🏗️',
                        'skills' => ['Site Blueprints', 'AutoCAD', 'WSH Compliance'],
                        'perk' => 'Project Bonus + Medical Cover',
                        'flag' => '🇮🇳 India, Mumbai',
                    ],
                    'warehouse supervisor' => [
                        'sector' => 'Warehouse & Logistics',
                        'icon' => '📦',
                        'skills' => ['Inventory Systems', 'Forklift Certified', 'Team Leadership'],
                        'perk' => 'Shift Allowance + Full Medical',
                        'flag' => '🇸🇬 Singapore',
                    ],
                    'warehouse coordinator' => [
                        'sector' => 'Supply Chain',
                        'icon' => '📋',
                        'skills' => ['SAP Logistics', 'Stock Auditing', 'Dispatch Control'],
                        'perk' => 'Direct Employer + AWS Bonus',
                        'flag' => '🇸🇬 Singapore, Jurong',
                    ],
                    'construction' => [
                        'sector' => 'Civil & Infrastructure',
                        'icon' => '🏗️',
                        'skills' => ['WSH Safety Coordinator', 'Site Supervision', 'Blueprint Reading'],
                        'perk' => 'Project Completion Bonus',
                        'flag' => '🇸🇬 Singapore, Kallang',
                    ],
                    'logistics' => [
                        'sector' => 'Freight & Operations',
                        'icon' => '🚢',
                        'skills' => ['Supply Chain ERP', 'Freight Forwarding', 'Vendor Management'],
                        'perk' => '5-Day Work Week + Transport',
                        'flag' => '🇸🇬 Singapore, Tuas',
                    ],
                    'manufacturing' => [
                        'sector' => 'Precision Engineering',
                        'icon' => '⚙️',
                        'skills' => ['ISO 9001 / Six Sigma', 'QA Auditing', 'Root Cause Analysis'],
                        'perk' => 'High-Tech Lab + Health Coverage',
                        'flag' => '🇲🇾 Malaysia, Shah Alam',
                    ],
                    'healthcare' => [
                        'sector' => 'Healthcare & Clinical',
                        'icon' => '🏥',
                        'skills' => ['Patient Care', 'Vital Signs', 'First Aid Certified'],
                        'perk' => 'Hospital Allowance + Training Grants',
                        'flag' => '🇸🇬 Singapore',
                    ],
                    'hospitality' => [
                        'sector' => 'Luxury Hospitality',
                        'icon' => '🏨',
                        'skills' => ['Guest Experience', 'Opera PMS', 'Operations Management'],
                        'perk' => '5-Star Hotel Privileges + Meals',
                        'flag' => '🇸🇬 Singapore, Marina Bay',
                    ],
                ];
            @endphp

            {{-- Job Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7">
                @forelse($featuredJobs as $job)
                    @php 
                        $companyName = $job->company->name ?? 'Corporate Partner';
                        $titleLower = strtolower($job->title);
                        
                        $matchedProfile = [
                            'sector' => 'Corporate Enterprise',
                            'icon' => '💼',
                            'skills' => ['Verified Vacancy', 'Full Time', 'Growth Track'],
                            'perk' => 'Comprehensive Benefits + Bonus',
                            'flag' => $job->location ?? ($job->country_code === 'IN' ? '🇮🇳 India' : '🇸🇬 Singapore'),
                        ];

                        foreach($jobProfiles as $key => $prof) {
                            if(str_contains($titleLower, $key)) {
                                $matchedProfile = $prof;
                                break;
                            }
                        }

                        if ($job->country_code === 'IN' && !str_contains($matchedProfile['flag'], '🇮🇳')) {
                            $matchedProfile['flag'] = '🇮🇳 ' . ($job->location ?: 'India');
                        }
                    @endphp
                    <article class="group relative bg-white rounded-3xl border border-slate-200/90 hover:border-slate-300 shadow-xs hover:shadow-xl hover:-translate-y-1.5 transition-all duration-200 p-7 flex flex-col justify-between overflow-hidden">
                        {{-- Top Accent Line on Hover --}}
                        <div class="absolute top-0 left-0 right-0 h-1 bg-secondary-500 opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>

                        <div>
                            {{-- Top Header Row: Sector Pill & Status Tags --}}
                            <div class="flex items-center justify-between gap-3 mb-5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-surface-sunken border border-border text-navy">
                                    <span>{{ $matchedProfile['icon'] }}</span>
                                    <span>{{ $matchedProfile['sector'] }}</span>
                                </span>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>Active</span>
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ Str::headline($job->work_mode ?? 'On-site') }}
                                    </span>
                                </div>
                            </div>

                            {{-- Role Title (Crisp Navy Text, No Electric Blue) --}}
                            <h3 class="font-heading font-extrabold text-xl sm:text-2xl text-navy group-hover:text-secondary-600 transition-colors line-clamp-1 mb-2">
                                <a href="{{ route('jobs.index', ['keyword' => $job->title]) }}" class="hover:underline">{{ $job->title }}</a>
                            </h3>

                            {{-- Verified Company & Location --}}
                            <div class="flex items-center gap-2 text-sm font-semibold text-text-secondary mb-4">
                                <span class="truncate text-navy font-bold">{{ $companyName }}</span>
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                <span>•</span>
                                <span class="text-xs text-text-muted font-medium truncate">{{ $matchedProfile['flag'] }}</span>
                            </div>

                            {{-- Skill Tag Pills --}}
                            <div class="flex flex-wrap gap-1.5 mb-5">
                                @foreach($matchedProfile['skills'] as $skill)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-[#f8fafc] text-slate-700 border border-slate-200 group-hover:bg-slate-100 transition-colors">
                                        {{ $skill }}
                                    </span>
                                @endforeach
                            </div>

                            {{-- Key Role Perk --}}
                            <div class="flex items-center gap-2 py-2 px-3 rounded-xl bg-surface-sunken text-xs text-text-secondary font-medium mb-6">
                                <svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span class="truncate">{{ $matchedProfile['perk'] }}</span>
                            </div>
                        </div>

                        {{-- Card Footer: Compensation & Interactive CTA --}}
                        <div class="pt-5 border-t border-border flex items-center justify-between gap-3">
                            <div>
                                <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider block">Monthly Compensation</span>
                                <span class="text-lg font-black text-secondary-600 font-heading">
                                    {{ $job->currency_code === 'INR' ? '₹' : $job->currency_code }} 
                                    @if($job->salary_min && $job->salary_max)
                                        {{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }}
                                    @elseif($job->salary_min)
                                        {{ number_format($job->salary_min) }}+
                                    @else
                                        Negotiable
                                    @endif
                                </span>
                            </div>

                            @if(in_array($job->id, $appliedJobIds ?? [], true))
                                <a href="{{ route('seeker.dashboard', ['tab' => 'applications']) }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-300 text-xs font-bold shadow-xs hover:bg-emerald-100 transition-all">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    <span>Applied</span>
                                </a>
                            @else
                                <a href="{{ route('jobs.index', ['keyword' => $job->title]) }}" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-navy hover:bg-slate-800 text-white text-xs font-bold shadow-xs hover:shadow-md transition-all">
                                    <span>Apply Now</span>
                                    <svg class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="col-span-full text-center py-16 text-text-muted">
                        No featured jobs found.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         4. SIMPLE 3-STEP PROCESS
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="py-20 lg:py-28 bg-white" x-data="{ visible: false, tab: 'seekers' }" x-intersect.threshold.15="visible = true">
        <div class="container mx-auto px-6 transition-all duration-700 transform" :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-xs font-bold uppercase tracking-widest text-secondary-600 mb-2 block">Process Workflow</span>
                <h2 class="text-3xl sm:text-4xl font-heading font-extrabold text-navy mb-3">How Luckyboss Works</h2>
                <p class="text-text-secondary text-base">Designed for maximum speed, accuracy, and transparent communication.</p>
            </div>

            <div class="max-w-4xl mx-auto">
                <div class="flex justify-center mb-12">
                    <div class="inline-flex bg-surface-sunken rounded-2xl p-1.5 border border-border shadow-inner">
                        <button @click="tab = 'seekers'" :class="tab === 'seekers' ? 'bg-navy text-white shadow-md' : 'text-text-secondary hover:text-text-primary'" class="px-7 py-2.5 rounded-xl font-bold text-sm transition-all cursor-pointer">
                            For Job Seekers
                        </button>
                        <button @click="tab = 'employers'" :class="tab === 'employers' ? 'bg-navy text-white shadow-md' : 'text-text-secondary hover:text-text-primary'" class="px-7 py-2.5 rounded-xl font-bold text-sm transition-all cursor-pointer">
                            For Employers
                        </button>
                    </div>
                </div>

                {{-- Seekers Flow --}}
                <div x-show="tab === 'seekers'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="grid md:grid-cols-3 gap-6">
                    <div class="bg-surface-sunken p-7 rounded-2xl border border-border text-center relative pt-10 shadow-xs">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-secondary-500 text-white flex items-center justify-center font-bold text-sm shadow-md border-2 border-white">1</div>
                        <div class="w-14 h-14 mx-auto bg-blue-50 text-accent rounded-xl flex items-center justify-center mb-5">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-navy mb-2">Create Profile</h3>
                        <p class="text-text-secondary text-xs leading-relaxed">Sign up and build your verified profile highlighting key certifications and experience.</p>
                    </div>
                    <div class="bg-surface-sunken p-7 rounded-2xl border border-border text-center relative pt-10 shadow-xs">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-secondary-500 text-white flex items-center justify-center font-bold text-sm shadow-md border-2 border-white">2</div>
                        <div class="w-14 h-14 mx-auto bg-blue-50 text-accent rounded-xl flex items-center justify-center mb-5">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-navy mb-2">AI Smart Match</h3>
                        <p class="text-text-secondary text-xs leading-relaxed">Our AI ranking engine scores your profile against direct employer requirements.</p>
                    </div>
                    <div class="bg-surface-sunken p-7 rounded-2xl border border-border text-center relative pt-10 shadow-xs">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-secondary-500 text-white flex items-center justify-center font-bold text-sm shadow-md border-2 border-white">3</div>
                        <div class="w-14 h-14 mx-auto bg-blue-50 text-accent rounded-xl flex items-center justify-center mb-5">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-navy mb-2">Apply & Get Hired</h3>
                        <p class="text-text-secondary text-xs leading-relaxed">Apply with one click, receive interview invites, and track job offers in real time.</p>
                    </div>
                </div>

                {{-- Employers Flow --}}
                <div x-show="tab === 'employers'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="grid md:grid-cols-3 gap-6">
                    <div class="bg-surface-sunken p-7 rounded-2xl border border-border text-center relative pt-10 shadow-xs">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-navy text-white flex items-center justify-center font-bold text-sm shadow-md border-2 border-white">1</div>
                        <div class="w-14 h-14 mx-auto bg-blue-50 text-accent rounded-xl flex items-center justify-center mb-5">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-navy mb-2">Register Company</h3>
                        <p class="text-text-secondary text-xs leading-relaxed">Setup your corporate hiring profile and verify your organization in under 2 minutes.</p>
                    </div>
                    <div class="bg-surface-sunken p-7 rounded-2xl border border-border text-center relative pt-10 shadow-xs">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-navy text-white flex items-center justify-center font-bold text-sm shadow-md border-2 border-white">2</div>
                        <div class="w-14 h-14 mx-auto bg-blue-50 text-accent rounded-xl flex items-center justify-center mb-5">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-navy mb-2">Publish Roles</h3>
                        <p class="text-text-secondary text-xs leading-relaxed">Post open vacancies with customized skill rubrics to attract targeted applicants.</p>
                    </div>
                    <div class="bg-surface-sunken p-7 rounded-2xl border border-border text-center relative pt-10 shadow-xs">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-navy text-white flex items-center justify-center font-bold text-sm shadow-md border-2 border-white">3</div>
                        <div class="w-14 h-14 mx-auto bg-blue-50 text-accent rounded-xl flex items-center justify-center mb-5">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-heading font-bold text-navy mb-2">Interview & Offer</h3>
                        <p class="text-text-secondary text-xs leading-relaxed">Review AI-ranked candidates, schedule video meetings, and issue offers.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         5. RECRUITMENT INTELLIGENCE & BLOG
    ═══════════════════════════════════════════════════════════════ --}}
    @if(isset($blogs) && $blogs->count() > 0)
    <section class="py-20 lg:py-28 bg-[#f8fafc] border-y border-border" x-data="{ visible: false }" x-intersect.threshold.15="visible = true">
        <div class="container mx-auto px-6 transition-all duration-700 transform" :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-5">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-secondary-600 mb-2 block">Career Insights</span>
                    <h2 class="text-3xl sm:text-4xl font-heading font-extrabold text-navy tracking-tight">
                        Career <span class="text-secondary-500">Knowledge</span>
                    </h2>
                    <p class="text-text-secondary text-base mt-1">Expert insights, advice, and trends in regional job recruitment.</p>
                </div>
                <a href="{{ route('blogs.index') }}" class="btn btn-outline btn-md shrink-0">View All Articles</a>
            </div>

            @php
                // Was three Unsplash photographs cycled by loop index, used
                // for every article whether or not it had its own image — the
                // blogs.image column was never read. A post with a real
                // picture now shows it; one without gets a lettered tile
                // instead of a stock photo of somebody else's office.
                $editorialFallback = null;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($blogs as $blog)
                    <article class="lb-card lb-post lb-reveal flex flex-col h-full">
                        {{-- A coloured spine that draws across on hover.

                             This replaced a 16:10 panel holding a single large
                             letter when a post had no picture — which looked
                             exactly like an image that had failed to load. An
                             article does not need a photograph to be worth
                             reading; it needs a headline you can see. So the
                             card is text-led, and a post that does have its own
                             image still shows it. --}}
                        <div class="lb-spine"></div>

                        @if($blog->image)
                            <a href="{{ route('blogs.show', $blog->slug) }}" class="block aspect-[16/9] overflow-hidden">
                                <img src="{{ asset($blog->image) }}" alt="{{ $blog->title }}"
                                     class="w-full h-full object-cover" loading="lazy">
                            </a>
                        @endif

                        <div class="p-6 flex-1 flex flex-col">
                            <div class="flex items-center gap-2.5 mb-3">
                                <span class="text-[10px] font-bold uppercase tracking-[0.12em] px-2.5 py-1 rounded-full"
                                      style="background:#EAF6F0;color:#127A50;">
                                    {{ $blog->category ?? 'Career' }}
                                </span>
                                <span class="text-[11px]" style="color:#9AA8BA;">
                                    {{ $blog->published_at ? $blog->published_at->format('j M Y') : now()->format('j M Y') }}
                                </span>
                            </div>

                            <h3 class="text-[19px] font-heading font-bold mb-2.5 leading-snug" style="color:#031F49;">
                                <a href="{{ route('blogs.show', $blog->slug) }}">{{ $blog->title }}</a>
                            </h3>

                            <p class="text-[13px] leading-relaxed mb-6 flex-1" style="color:#7A8AA0;">
                                {{ $blog->short_description ?? Str::limit(strip_tags($blog->content), 130) }}
                            </p>

                            <a href="{{ route('blogs.show', $blog->slug) }}"
                               class="inline-flex items-center gap-1.5 text-[13px] font-bold pt-4"
                               style="color:#18A66A;border-top:1px solid #EEF2F7;">
                                Read the guide
                                <svg class="lb-arrow w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                </svg>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         6. DUAL CALL-TO-ACTION BANNER
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="py-20 lg:py-28 bg-white" x-data="{ visible: false }" x-intersect.threshold.15="visible = true">
        <div class="container mx-auto px-6 transition-all duration-700 transform" :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'">
            <div class="bg-gradient-to-r from-[#031533] via-[#062454] to-[#031533] rounded-3xl p-8 md:p-14 shadow-2xl overflow-hidden relative text-white">
                <div class="absolute top-0 right-0 w-80 h-80 bg-secondary-500/15 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>
                
                <div class="relative z-10 grid md:grid-cols-2 gap-10 divide-y md:divide-y-0 md:divide-x divide-white/15">
                    <div class="text-center md:text-left md:pr-8">
                        <span class="text-xs font-bold uppercase tracking-widest text-secondary-300 block mb-2 font-sans">Job Seekers</span>
                        <h2 class="text-2xl sm:text-3xl font-heading font-extrabold mb-3">Looking For Your Next Role?</h2>
                        <p class="text-blue-100/90 mb-7 text-sm leading-relaxed">Create your free profile to get discovered by verified employers and apply to open jobs with one click.</p>
                        <a href="{{ route('register.seeker') }}" class="btn bg-white text-navy hover:bg-surface font-bold border-0 px-8 py-3 text-sm shadow-lg hover:shadow-xl">
                            Start Free Job Search
                        </a>
                    </div>
                    <div class="text-center md:text-left pt-8 md:pt-0 md:pl-10">
                        <span class="text-xs font-bold uppercase tracking-widest text-secondary-300 block mb-2 font-sans">Corporate Employers</span>
                        <h2 class="text-2xl sm:text-3xl font-heading font-extrabold mb-3">Hiring Quality Talent?</h2>
                        <p class="text-blue-100/90 mb-7 text-sm leading-relaxed">Publish vacancies, review AI-scored candidate profiles, and build your team faster across the region.</p>
                        <a href="{{ route('register.employer') }}" class="btn bg-secondary-500 hover:bg-secondary-600 text-white font-bold border-0 px-8 py-3 text-sm shadow-lg hover:shadow-xl">
                            Post Jobs & Find Talent
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
