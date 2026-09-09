{{--
    Lucky AI — the screen a candidate lands on after signing in, and now the
    site root for them.

    It carries the same furniture as the public home on purpose: the header with
    the logo and the pill nav (from the layout), the question, and the rolling
    line under it. Signing in should change what the page offers, not what the
    product looks like.
--}}
<x-seeker-sidebar title="Lucky AI">

    {{-- Written as plain CSS, not utilities: the Tailwind bundle is prebuilt
         with no Node step, so a class invented here would not exist at runtime.
         Same keyframes as the public home so the two lines move identically. --}}
    @include('agent.partials.history-style')

    <style>
        @keyframes lb-roll {
            0%, 4%    { opacity: 0; transform: translateY(8px); }
            12%, 90%  { opacity: 1; transform: translateY(0); }
            98%, 100% { opacity: 0; transform: translateY(-8px); }
        }
        .lb-roller { overflow: hidden; text-align: center; min-height: 56px; }
        @media (min-width: 640px) { .lb-roller { min-height: 34px; } }
        .lb-roller [data-roller] { display: inline-block; animation: lb-roll 3s ease-in-out infinite; }
        /* Motion is decoration. Anyone who asked the OS for less of it gets the
           term sitting still, which reads exactly as well. */
        @media (prefers-reduced-motion: reduce) { .lb-roller [data-roller] { animation: none; } }
    </style>

    {{-- The agent owns the first screenful. The footer and everything else
         begins below the fold, so signing in lands on the agent and nothing
         else — sir's note that the buttons should not all show at once.
         Inline height because no viewport utility of this shape is in the
         prebuilt bundle. --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 flex flex-col justify-center space-y-8"
         style="min-height: calc(100vh - 96px); padding-top: 2rem; padding-bottom: 2rem;">

        <div class="text-center space-y-2">
            <p class="text-sm font-bold text-accent">Hello, {{ Str::before($user->name, ' ') }}</p>
            <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-navy">What are we doing today?</h1>
            @unless ($readiness['ready'])
                <p class="text-sm text-text-secondary leading-relaxed max-w-lg mx-auto">
                    Answer a few short questions and we can start scoring real vacancies against you.
                    We only ask for what we actually use.
                </p>
            @endunless
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($cards as $card)
                @php
                    // No primary card. One navy tile among three white ones put
                    // the darkest object on the page in the top-left corner, where
                    // the eye lands first, and made the grid read as two cards and
                    // a button. Emphasis is order and wording now.
                    $isPrimary = false;
                    $shell = 'w-full text-left block rounded-2xl border p-5 transition-all duration-150 group '
                        .($isPrimary ? 'bg-navy border-navy shadow-md cursor-pointer' : 'bg-white border-border shadow-xs hover:border-accent');
                @endphp

                @if (isset($card['post']))
                    {{-- Starting a conversation writes a row, so it is a POST. --}}
                    <form method="POST" action="{{ $card['post'] }}">
                        @csrf
                        <button type="submit" class="{{ $shell }}">
                            <span class="block text-sm font-bold {{ $isPrimary ? 'text-white' : 'text-navy group-hover:text-accent' }}">{{ $card['label'] }}</span>
                            <span class="block text-xs mt-1 leading-relaxed {{ $isPrimary ? 'text-white/70' : 'text-text-muted' }}">{{ $card['sub'] }}</span>
                        </button>
                    </form>
                @else
                    <a href="{{ $card['url'] }}" class="{{ $shell }}">
                        <span class="block text-sm font-bold {{ $isPrimary ? 'text-white' : 'text-navy group-hover:text-accent' }}">{{ $card['label'] }}</span>
                        <span class="block text-xs mt-1 leading-relaxed {{ $isPrimary ? 'text-white/70' : 'text-text-muted' }}">{{ $card['sub'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>

        {{-- The rolling line, same motion as the public home. Height is reserved
             rather than measured: titles run from "Forklift Driver" to "Supply
             Chain & Logistics Operations Lead", and without the reserve a long
             one shoves the cards down every three seconds. --}}
        @if (! empty($rolling['terms']))
            <div class="text-center">
                <p class="text-[11px] font-bold uppercase tracking-widest mb-1" style="color:var(--color-text-muted);">
                    {{ $rolling['label'] }}
                </p>
                <p class="lb-roller flex items-center justify-center px-4 text-lg sm:text-xl font-bold"
                   style="color:#031F49;" aria-live="polite">
                    <span data-roller>{{ $rolling['terms'][0] }}</span>
                </p>
            </div>
        @endif

        @if ($history->isNotEmpty())
            <div class="max-w-lg mx-auto w-full">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-muted mb-2 text-center">Carry on where you left off</p>
                <div class="space-y-2">
                    @foreach ($history as $conversation)
                        {{-- The clear control cannot live inside the row's own
                             <a>: a form nested in an anchor is invalid, and the
                             browser lifts it out, which puts the button
                             somewhere nobody intended. The row and the form are
                             siblings, and the wrapper positions the form over
                             the row's right edge. --}}
                        <div class="lb-histrow">
                            <a href="{{ route('seeker.chat.show', $conversation) }}"
                               class="flex items-center justify-between gap-3 rounded-xl border border-border bg-white px-4 py-2.5 hover:border-accent transition-colors">
                                <span class="text-xs font-bold text-navy truncate">{{ $conversation->title() }}</span>
                                <span class="text-[11px] text-text-muted shrink-0 lb-hist-meta">
                                    {{ $conversation->status === 'done' ? 'Finished' : 'Unfinished' }} &middot; {{ $conversation->created_at?->diffForHumans() }}
                                </span>
                            </a>

                            <form method="POST" action="{{ route('seeker.chat.destroy', $conversation) }}"
                                  class="lb-hist-x"
                                  onsubmit="return confirm('Clear this conversation? It cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" aria-label="Clear this conversation">&times;</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <p class="text-center text-[11px] text-text-muted leading-relaxed max-w-lg mx-auto">
            Lucky AI reads your profile and scores real Lucky Boss vacancies against it.
            It never invents a match score, and it never applies anywhere outside Lucky Boss.
        </p>

    </div>

    <script>
        // Restarting the animation on each change makes the timer the single
        // source of phase — the term can never swap while it is fully visible,
        // whatever the browser is doing with animation events. Same approach as
        // the public home, for the same reason.
        (function () {
            var terms = @json($rolling['terms'] ?? []);
            var roller = document.querySelector('[data-roller]');
            if (!roller || terms.length < 2) return;

            var i = 0;
            setInterval(function () {
                i = (i + 1) % terms.length;
                roller.style.animation = 'none';
                void roller.offsetWidth;
                roller.textContent = terms[i];
                roller.style.animation = '';
            }, 3000);
        })();
    </script>
</x-seeker-sidebar>
