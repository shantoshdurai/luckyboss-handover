<x-layouts.app title="Browse jobs by trade | Lucky Boss">
    {{--
        This page used to be a dark banner over a grid of Unsplash photographs,
        matched to a category by string-searching its slug, under the words
        "Discover verified career opportunities tailored to your specialized
        skills and experience across premier industries." Three things wrong
        with that, and they are all the same thing:

        - The photographs were stock. A borrowed picture of somebody else's
          building site says nothing true about who is hiring on ours, and the
          page could not render at all without reaching out to a third party.
        - The dark header contradicted the whole site. Light only, on purpose:
          our candidates read this outdoors, on cheap phones, in sunlight.
        - The words were filler. A visitor arrives here asking one question --
          "is my trade on this site?" -- and nothing on the page answered it.

        So the cards are text-led and made of real data now: the trades inside
        each category, named, from the same `WorkTaxonomy` that the Flutter app
        and both agents speak. A mason can see the word Mason without clicking.
    --}}
    <style>
        .lb-card {
            position: relative;
            background: #fff;
            border: 1px solid var(--color-border);
            border-radius: 18px;
            box-shadow: 0 1px 2px rgba(3,31,73,.05), 0 6px 16px -10px rgba(3,31,73,.28);
            transition: transform .3s cubic-bezier(.22,.61,.36,1),
                        box-shadow .3s cubic-bezier(.22,.61,.36,1),
                        border-color .3s ease;
        }
        .lb-card:hover {
            transform: translateY(-4px);
            border-color: #C9E6D8;
            box-shadow: 0 18px 40px -24px rgba(3,31,73,.45);
        }

        /* The coloured spine the home page's editorial cards use, chosen there
           for the reason it applies here too: it reads as a decision, where a
           placeholder image reads as a missing one. */
        .lb-spine {
            position: absolute; left: 18px; right: 18px; top: -1px;
            height: 2px; border-radius: 2px;
            background: linear-gradient(90deg, #18A66A, #2563EB);
            transform: scaleX(.22); transform-origin: left;
            transition: transform .45s cubic-bezier(.22,.61,.36,1);
        }
        .lb-card:hover .lb-spine { transform: scaleX(1); }

        .lb-count {
            font-size: 11px; font-weight: 700; letter-spacing: .04em;
            padding: 3px 9px; border-radius: 999px; white-space: nowrap;
            background: #F2F7FF; color: #2563EB; border: 1px solid #E1ECFB;
            transition: background .3s ease, color .3s ease, border-color .3s ease;
        }
        .lb-card:hover .lb-count { background: #031F49; color: #fff; border-color: #031F49; }

        /* A trade name, not something the visitor can act on separately -- so
           it stays quiet enough not to look clickable inside a card that is
           itself one big link. */
        .lb-trade {
            font-size: 12px; font-weight: 600; line-height: 1.1;
            padding: 5px 9px; border-radius: 999px;
            background: var(--color-surface); color: var(--color-text-secondary);
            border: 1px solid var(--color-border);
        }
        .lb-trade-more { background: #fff; color: var(--color-text-muted); }

        .lb-arrow { transition: transform .3s cubic-bezier(.22,.61,.36,1); }
        .lb-card:hover .lb-arrow { transform: translateX(5px); }
    </style>

    <section class="py-12 lg:py-16">
        <div class="container-app max-w-3xl">
            <p class="text-[11px] font-bold uppercase tracking-widest mb-3" style="color:var(--color-text-muted);">
                Browse by trade
            </p>
            <h1 class="font-heading font-bold tracking-tight leading-tight text-3xl sm:text-4xl mb-3 text-navy">
                Fourteen kinds of work. Find yours.
            </h1>
            <p class="text-text-secondary leading-relaxed">
                These are the same categories Lucky AI asks about, and the same ones the app uses,
                so whatever you pick here means the same thing everywhere else on Lucky Boss.
            </p>
        </div>
    </section>

    <section class="pb-16">
        <div class="container-app">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($categories as $category)
                    @php
                        $roles = $trades[$category->id] ?? [];
                        $open = (int) ($category->jobs_count ?? 0);
                        // A trade nobody has posted to yet is not a dead end: it
                        // is the moment to tell us you do that work, so we can
                        // watch for it. Sending the visitor to an empty results
                        // page instead is the version of this that wastes a tap.
                        $href = $open > 0
                            ? route('jobs.index', ['category' => $category->id])
                            : (auth()->check() ? route('seeker.home') : route('register.seeker'));
                    @endphp
                    <a href="{{ $href }}" class="lb-card p-5 flex flex-col justify-between">
                        <span class="lb-spine" aria-hidden="true"></span>
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <h2 class="font-heading font-bold text-lg text-navy leading-snug">
                                    {{ $category->name }}
                                </h2>
                                @if($open > 0)
                                    {{-- Shown only when there is something to show.
                                         "0 Jobs Available" is the worst thing this
                                         card can say, and it used to say it. --}}
                                    <span class="lb-count">{{ $open }} open</span>
                                @endif
                            </div>

                            @if($roles)
                                <p class="text-[11px] font-bold uppercase tracking-widest mb-2" style="color:var(--color-text-muted);">
                                    {{ count($roles) }} trades
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach(array_slice($roles, 0, 5) as $role)
                                        <span class="lb-trade">{{ $role }}</span>
                                    @endforeach
                                    @if(count($roles) > 5)
                                        <span class="lb-trade lb-trade-more">+{{ count($roles) - 5 }} more</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-between gap-3 mt-4 pt-4 border-t border-border text-sm font-bold text-accent">
                            <span>{{ $open > 0 ? 'See the vacancies' : 'Nothing open yet, tell us you do this' }}</span>
                            <svg class="lb-arrow w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full py-16 text-center text-text-muted">
                        No categories found.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="py-14 bg-white border-t border-border">
        <div class="container-app max-w-2xl text-center">
            <h2 class="text-2xl font-heading font-bold text-navy mb-3">Not sure which one is yours?</h2>
            <p class="text-text-secondary mb-7 leading-relaxed">
                You do not have to know our category names. Tell Lucky AI what work you do,
                in your own words, and it will find the vacancies that actually match.
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ auth()->check() ? route('seeker.home') : route('register.seeker') }}" class="btn btn-primary btn-md">
                    Talk to Lucky AI
                </a>
                <a href="{{ route('jobs.index') }}" class="btn btn-outline btn-md">Search all jobs</a>
            </div>
        </div>
    </section>
</x-layouts.app>
