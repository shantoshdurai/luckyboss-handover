{{--
    One question at a time.

    Written as a plain form, not a chat widget: it works with no JavaScript, it
    survives a validation failure, and on a cheap phone on site a big tappable
    answer beats a text box. The bubble is presentation only.
--}}
<x-seeker-sidebar title="Lucky AI">
    <div class="max-w-xl mx-auto px-4 sm:px-6 py-12 space-y-6">

        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('seeker.home') }}" class="text-xs font-bold text-text-muted hover:text-navy">&larr; Back</a>
            <p class="text-[11px] font-bold uppercase tracking-wider text-text-muted">
                {{ $remaining }} {{ Str::plural('question', $remaining) }} left
            </p>
        </div>

        {{-- The agent's line. --}}
        <div class="flex items-start gap-3">
            <span class="w-9 h-9 rounded-2xl bg-accent/10 text-accent grid place-items-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
            </span>
            <div class="bg-white rounded-2xl border border-border shadow-xs px-5 py-4 min-w-0">
                <p class="text-base font-bold text-navy">{{ $question['prompt'] }}</p>
                <p class="text-xs text-text-muted mt-1 leading-relaxed">{{ $question['help'] }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('seeker.agent.answer') }}" class="space-y-4">
            @csrf

            @if ($question['type'] === 'choice')
                <div class="space-y-2">
                    @foreach ($question['choices'] as $label => $value)
                        <button type="submit" name="answer" value="{{ $value }}"
                                class="w-full text-left px-5 py-3.5 rounded-2xl border border-border bg-white text-sm font-bold text-navy hover:border-accent hover:text-accent transition-all duration-150 cursor-pointer shadow-xs">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            @else
                <div>
                    <input type="text" name="answer" value="{{ old('answer') }}"
                           placeholder="{{ $question['placeholder'] }}"
                           class="form-input" autofocus>
                    @error('answer')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary btn-sm font-bold text-xs">Next</button>
            @endif

            {{-- Skipping is real, and the next screen says what it cost. We do
                 not fill the gap with a default. --}}
            <div class="pt-1">
                <button type="submit" name="skip" value="1"
                        class="text-xs font-bold text-text-muted hover:text-navy cursor-pointer">
                    Skip this
                </button>
            </div>
        </form>

    </div>
</x-seeker-sidebar>
