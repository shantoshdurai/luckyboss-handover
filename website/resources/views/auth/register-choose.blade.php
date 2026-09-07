{{--
    Account type — the first step of registering.

    `/register` used to redirect straight to the job seeker form, so an employer
    arriving from the "Register" button landed on the wrong sign-up and had to
    notice a link at the bottom of the page to escape. TickBig asks the question
    outright (Individual / Company / Institution) before showing a single field,
    and sir asked for the same.

    Two options rather than three: Luckyboss has exactly two kinds of account,
    and they lead to genuinely different portals. Inventing a third to look like
    theirs would be decoration.
--}}
<x-layouts.app :bare="true" title="Create your account — Luckyboss">

@php
    $branding = app(\App\Services\SiteSettingsService::class)->branding();

    $options = [
        [
            'label' => 'I am looking for work',
            'note'  => 'Job seeker',
            'href'  => route('register.seeker'),
            'icon'  => 'M20.25 14.15v4.073a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a12.06 12.06 0 0 1-6.596 0l-1.32-.377a2.25 2.25 0 0 1-1.632-2.163V14.15M3.75 8.25v10.5a2.25 2.25 0 0 0 2.25 2.25h12a2.25 2.25 0 0 0 2.25-2.25V8.25M3.75 8.25h16.5M9 5.25V4.5A1.5 1.5 0 0 1 10.5 3h3a1.5 1.5 0 0 1 1.5 1.5v.75',
        ],
        [
            'label' => 'I am hiring',
            'note'  => 'Employer',
            'href'  => route('register.employer'),
            'icon'  => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        ],
    ];
@endphp


    <div class="min-h-screen flex flex-col" style="background:var(--color-surface);">

        <header class="w-full">
            <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center flex-shrink-0" aria-label="Luckyboss home">
                    <img src="{{ $branding['logo_url'] }}"
                         alt="Luckyboss Employment Agency Pte. Ltd"
                         class="h-9 sm:h-11 w-auto object-contain">
                </a>

                <p class="text-sm text-right flex-shrink-0" style="color:var(--color-text-secondary);">
                    <span class="hidden sm:inline">Already have an account?</span>
                    <a href="{{ route('login') }}"
                       class="font-semibold hover:underline whitespace-nowrap" style="color:#18A66A;">Sign in</a>
                </p>
            </div>
        </header>

        <main class="flex-1 flex items-start justify-center px-6 pb-20 pt-10 sm:pt-20">
            <div class="w-full" style="max-width:34rem;">

                <div class="text-center mb-9">
                    <h1 class="text-[26px] sm:text-[32px] font-heading font-bold leading-tight" style="color:#031F49;">
                        What kind of account?
                    </h1>
                    <span class="inline-block mt-3 rounded-full" style="width:38px;height:3px;background:#18A66A;"></span>
                </div>


                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($options as $option)
                        <a href="{{ $option['href'] }}"
                           class="lb-choice rounded-2xl px-6 py-8 text-center block">
                            <span class="lb-choice-tile mx-auto mb-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $option['icon'] }}"/>
                                </svg>
                            </span>
                            <span class="block font-bold text-base" style="color:#031F49;">{{ $option['label'] }}</span>
                            <span class="block text-sm mt-1" style="color:var(--color-text-muted);">{{ $option['note'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </main>
    </div>

    <style>
        /* Written as plain CSS: this project ships a prebuilt Tailwind bundle
           with no Node step, so a class invented here would not exist at
           runtime. Matches the home page doors, minus the tilt sir disliked. */
        .lb-choice {
            background: #fff;
            border: 1px solid var(--color-border);
            transition: transform .3s cubic-bezier(.22,.61,.36,1),
                        box-shadow .3s cubic-bezier(.22,.61,.36,1),
                        border-color .3s ease;
        }
        .lb-choice:hover {
            transform: translateY(-4px);
            border-color: #C9E6D8;
            box-shadow: 0 18px 40px -24px rgba(3,31,73,.45);
        }
        .lb-choice-tile {
            width: 52px; height: 52px; border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            background: #EAF6F0; color: #18A66A;
            transition: background .3s ease, color .3s ease;
        }
        .lb-choice:hover .lb-choice-tile { background: #18A66A; color: #fff; }

        @media (prefers-reduced-motion: reduce) {
            .lb-choice, .lb-choice-tile { transition: none; }
            .lb-choice:hover { transform: none; }
        }
    </style>
</x-layouts.app>
