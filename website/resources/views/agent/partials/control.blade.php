{{--
    The answer control for whichever question is on screen.

    Shared by the candidate's Lucky AI and the employer's hiring agent, because
    the two flows differ in what they ask, never in how an answer is given.
    Callers pass:

      $action      — where the answer is posted
      $question    — the question array, or null when the script is finished
      $options     — chips for an `options`/`multi` question
      $outcome     — the finished payload, or null
      $outcomeView — the view that renders that payload (each side has its own,
                     because a candidate ends on jobs and an employer on people)

    Chips inline, not behind a "Choose one" button — the app shows the trades
    right there, because the point of the whole screen is that someone taps what
    they mean rather than hunting for it. Long lists collapse to the first
    `preview` entries with a "Show all N", and the search box appears only when
    there is enough to search, exactly as `SearchableChipPicker` does it.

    `data-no-soft-nav`: the conversation drives itself in place, and the layout's
    soft navigation would otherwise take the submit and swap the whole <main>.
--}}
@php
    $outcomeView = $outcomeView ?? null;
@endphp

@if ($question)
    @php
        $preview = $question['preview'] ?? 10;
        $searchable = count($options) > $preview;
        $allowOther = (bool) ($question['allow_other'] ?? false);
        $optional = (bool) ($question['optional'] ?? false);
        // Defined in agent/partials/assets.blade.php, not as utilities: the
        // hover background this used to rely on is absent from the prebuilt
        // bundle, and hovering the confirm chip turned its label white on white.
        $chip = 'lb-chip';
    @endphp

    <form method="POST" action="{{ $action }}"
          class="pt-1 lb-rise" data-no-soft-nav data-chat-form
          x-data="{
              q: '',
              all: false,
              other: false,
              picked: [],
              has(v) { return this.picked.indexOf(v) !== -1; },
              toggle(v) {
                  var i = this.picked.indexOf(v);
                  if (i === -1) this.picked.push(v); else this.picked.splice(i, 1);
              },
              shows(label, index) {
                  if (this.q !== '') return label.toLowerCase().indexOf(this.q.toLowerCase()) !== -1;
                  return this.all || index < {{ $preview }};
              }
          }">
        @csrf

        @if (! empty($question['help']))
            <p class="text-xs text-text-muted mb-3 leading-relaxed">{{ $question['help'] }}</p>
        @endif

        @if ($question['type'] === 'confirm')
            <button type="submit" name="answer" value="{{ $question['confirm_label'] }}"
                    class="{{ $chip }} lb-chip-primary">
                {{ $question['confirm_label'] }}
            </button>

        @elseif ($question['type'] === 'choice')
            <div class="flex flex-wrap gap-2">
                @foreach ($question['choices'] as $label => $value)
                    <button type="submit" name="answer" value="{{ $value }}"
                            class="{{ $chip }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

        @elseif ($question['type'] === 'multi')
            {{-- Pick as many as you like, then Done. Multi-select deliberately
                 does not advance on a tap: the app's rule is that auto-advancing
                 "fights someone who has not finished choosing". --}}
            <input type="hidden" name="answer" :value="picked.join(', ')">

            @if ($searchable)
                <input type="text" x-model="q" placeholder="{{ $question['panel_title'] }}" class="form-input mb-3">
            @endif

            <div class="flex flex-wrap gap-2">
                @foreach ($options as $i => $option)
                    <button type="button" x-show="shows(@js($option), {{ $i }})" x-cloak
                            @click="toggle(@js($option))"
                            :class="has(@js($option)) ? 'lb-chip-on' : ''"
                            class="{{ $chip }} inline-flex items-center gap-2">
                        <span>{{ $option }}</span>
                        <span x-text="has(@js($option)) ? '&times;' : '+'" class="text-base leading-none"></span>
                    </button>
                @endforeach
            </div>

            @if ($searchable)
                <button type="button" x-show="! all && q === ''" @click="all = true"
                        class="mt-3 text-xs font-bold text-accent hover:underline cursor-pointer">
                    Show all {{ count($options) }}
                </button>
            @endif

            <div class="mt-4 flex items-center gap-3">
                <button type="submit" class="btn btn-primary btn-sm font-bold text-xs" :disabled="picked.length === 0">
                    Done
                </button>
                <span class="text-[11px] text-text-muted" x-text="picked.length ? picked.length + ' picked' : 'Pick at least one'"></span>
            </div>

        @elseif ($question['type'] === 'options')
            {{-- `allow_other` exists for the employer's site location: the chips
                 are the places we already have work, and a new employer's yard
                 is very often not one of them. Offering only those would make a
                 real answer impossible to give. The input is disabled while
                 hidden so the browser never submits two `answer` fields. --}}
            <div x-show="! other">
                @if ($searchable)
                    <input type="text" x-model="q" placeholder="{{ $question['panel_title'] }}" class="form-input mb-3">
                @endif

                <div class="flex flex-wrap gap-2">
                    @forelse ($options as $i => $option)
                        <button type="submit" name="answer" value="{{ $option }}"
                                x-show="shows(@js($option), {{ $i }})" x-cloak
                                class="{{ $chip }}">
                            {{ $option }}
                        </button>
                    @empty
                        @unless ($allowOther)
                            <p class="text-xs text-text-muted">Nothing to choose from here yet.</p>
                        @endunless
                    @endforelse

                    @if ($allowOther)
                        <button type="button" @click="other = true"
                                class="{{ $chip }} lb-chip-muted">
                            {{ $question['other_label'] ?? 'Somewhere else' }}
                        </button>
                    @endif
                </div>

                @if ($searchable)
                    <button type="button" x-show="! all && q === ''" @click="all = true"
                            class="mt-3 text-xs font-bold text-accent hover:underline cursor-pointer">
                        Show all {{ count($options) }}
                    </button>
                @endif
            </div>

            @if ($allowOther)
                <div x-show="other" x-cloak class="space-y-3">
                    <input type="text" name="answer" :disabled="! other"
                           placeholder="{{ $question['placeholder'] ?? '' }}" class="form-input">
                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn btn-primary btn-sm font-bold text-xs">Send</button>
                        <button type="button" @click="other = false"
                                class="text-xs font-bold text-text-muted hover:text-navy cursor-pointer">
                            Back to the list
                        </button>
                    </div>
                </div>
            @endif

        @else
            <div class="space-y-3">
                <input type="text" name="answer" value="{{ old('answer') }}"
                       placeholder="{{ $question['placeholder'] ?? '' }}" class="form-input" autofocus>
                <div class="flex items-center gap-3">
                    <button type="submit" class="btn btn-primary btn-sm font-bold text-xs">Send</button>
                    @if ($optional)
                        {{-- A real answer, not a way out: the script records that
                             this was skipped, and the matcher then drops the
                             dimension rather than inventing a figure for it. --}}
                        <button type="submit" name="answer" value="{{ $question['skip_value'] ?? 'Skip' }}"
                                class="text-xs font-bold text-text-muted hover:text-navy cursor-pointer">
                            {{ $question['skip_label'] ?? 'Skip this' }}
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </form>
@endif

@if ($outcome && $outcomeView)
    @include($outcomeView, ['outcome' => $outcome])
@endif
