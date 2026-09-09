<x-seeker-sidebar title="My Profile">
    @php
        // Which section owns each field, so a validation failure reopens the
        // section that actually failed. Same approach as the registration
        // wizard's $stepFields: without it an error renders on a panel the
        // candidate cannot see and the Save button looks dead.
        $sectionFields = [
            'about' => ['professional_summary'],
            'experience' => ['current_title', 'years_experience', 'notice_period', 'qualification', 'current_location'],
            'skills' => ['skills'],
            'preferences' => ['preferred_location', 'expected_salary', 'preferred_currency', 'availability'],
        ];
        $errorSection = collect($sectionFields)
            ->search(fn ($fields) => collect($fields)->contains(fn ($f) => $errors->has($f)));
        $errorSection = $errorSection === false ? null : $errorSection;
        $openSection = $errorSection ?: session('open_section');
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 space-y-6"
         x-data="{
             tab: '{{ $openSection ?: collect($sections)->keys()->first() }}',
             {{-- Unescaped on purpose: this is one of four literal section keys
                  computed above, never user input. Through {{ }} the quotes
                  become &#039; — which the browser still decodes correctly, but
                  it makes the attribute unreadable in view-source. --}}
             editing: {!! $errorSection ? "'".$errorSection."'" : 'null' !!}
         }">

        {{-- Identity. What an employer sees first, so it is what the candidate
             sees first too. --}}
        <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center gap-6">

                <div class="flex items-center gap-5 min-w-0 flex-1">
                    @if ($profile?->profile_photo_path)
                        <img src="{{ asset($profile->profile_photo_path) }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-2xl object-cover border border-border shrink-0">
                    @else
                        <div class="w-20 h-20 rounded-2xl bg-navy text-white grid place-items-center text-xl font-heading font-extrabold shrink-0">
                            {{ Str::upper(Str::substr($user->name, 0, 2)) }}
                        </div>
                    @endif

                    <div class="min-w-0 space-y-1">
                        <h1 class="text-xl sm:text-2xl font-heading font-extrabold text-navy truncate">{{ $user->name }}</h1>
                        <p class="text-sm text-text-secondary">
                            {{ $profile?->current_title ?: 'Add your trade or job title' }}
                        </p>
                        <p class="text-xs text-text-muted">
                            {{ $profile?->current_location ?: 'Location not set' }}
                            @if ($profile?->years_experience !== null)
                                &middot; {{ $profile->years_experience }} {{ Str::plural('year', $profile->years_experience) }} experience
                            @endif
                        </p>
                    </div>
                </div>

                {{-- A ring, not a bar. Tapping it goes to the profile editor,
                     which is where the missing pieces are filled in. --}}
                <a href="{{ route('seeker.profile.edit') }}" class="shrink-0 flex items-center gap-4 group">
                    @php
                        $radius = 26;
                        $circumference = 2 * M_PI * $radius;
                        $offset = $circumference * (1 - $completion / 100);
                        $ringTone = $completion >= 80 ? 'text-emerald-500' : ($completion >= 40 ? 'text-accent' : 'text-amber-500');
                    @endphp
                    <span class="relative grid place-items-center">
                        <svg class="w-16 h-16 -rotate-90" viewBox="0 0 64 64">
                            <circle cx="32" cy="32" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="5" class="text-slate-200"/>
                            <circle cx="32" cy="32" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"
                                    class="{{ $ringTone }}"
                                    stroke-dasharray="{{ round($circumference, 2) }}"
                                    stroke-dashoffset="{{ round($offset, 2) }}"/>
                        </svg>
                        <span class="absolute text-sm font-extrabold text-navy">{{ $completion }}%</span>
                    </span>
                    <span class="hidden sm:flex flex-col">
                        <span class="block text-xs font-bold text-navy group-hover:text-accent">Profile complete</span>
                        <span class="block text-xs text-text-muted">Edit your profile &rarr;</span>
                    </span>
                </a>
            </div>
        </div>

        {{-- Resume, pinned above everything. TickBig do the same, and it is the
             single action that most improves a candidate's matches. --}}
        <div class="bg-white rounded-2xl border border-border p-6 shadow-xs">
            @if ($profile?->resume_file_name)
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 grid place-items-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-navy truncate">{{ $profile->resume_file_name }}</p>
                            <p class="text-xs text-text-muted">Sent with every application you make.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        @if ($profile->resume_path)
                            <a href="{{ asset($profile->resume_path) }}" target="_blank" rel="noopener" class="text-xs font-bold text-accent hover:underline">View</a>
                        @endif
                        <a href="{{ route('seeker.resume.choose') }}" class="btn btn-outline btn-sm font-bold text-xs">Replace</a>
                    </div>
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-11 h-11 rounded-2xl bg-accent/10 text-accent grid place-items-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.9A5 5 0 1115.9 6H16a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-navy">No resume yet</p>
                            <p class="text-xs text-text-muted">Add one and we&rsquo;ll fill in your details and show your matches.</p>
                        </div>
                    </div>
                    <a href="{{ route('seeker.resume.choose') }}" class="btn btn-primary btn-sm font-bold text-xs shrink-0">Add my resume</a>
                </div>
            @endif
        </div>

        {{-- Section tabs. Read-only by default; each pencil opens the editor at
             that section rather than at the top of a very long form. --}}
        <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
            <div class="flex flex-wrap gap-1 p-2 border-b border-border bg-slate-50">
                @foreach ($sections as $key => $section)
                    <button type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-white text-navy shadow-xs border-border' : 'text-text-secondary border-transparent hover:text-navy'"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold border transition-colors cursor-pointer flex items-center gap-1.5">
                        <span>{{ $section['label'] }}</span>
                        @unless ($section['filled'])
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500" title="Nothing here yet"></span>
                        @endunless
                    </button>
                @endforeach
            </div>

            @php
                $editUrl = fn (?string $anchor) => route('seeker.profile.edit').($anchor ? '#'.$anchor : '');
            @endphp

            {{-- About --}}
            <div x-show="tab === 'about'" x-cloak class="p-6 sm:p-8 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="text-base font-bold text-navy">About you</h2>
                    <button type="button" x-show="editing !== 'about'"
                            @click="editing = 'about'"
                            class="text-xs font-bold text-accent hover:underline shrink-0 cursor-pointer">Edit</button>
                </div>
                <div x-show="editing !== 'about'">
                    @if (filled($profile?->professional_summary))
                        <p class="text-sm text-text-secondary leading-relaxed whitespace-pre-line">{{ $profile->professional_summary }}</p>
                    @else
                        <p class="text-sm text-text-muted italic">Nothing here yet. A couple of lines about the work you do helps employers place you quickly.</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('seeker.profile.section', 'about') }}"
                      x-show="editing === 'about'" x-cloak class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label for="professional_summary" class="block text-xs font-bold text-navy mb-2">A few lines about the work you do</label>
                        <textarea id="professional_summary" name="professional_summary" rows="5" class="form-input" placeholder="I am a site electrician with 5 years on commercial fit-outs...">{{ old('professional_summary', $profile?->professional_summary) }}</textarea>
                        <p class="text-[11px] text-text-muted mt-2 leading-relaxed">Employers read this first. Plain words about what you do and where you have done it beat anything fancy.</p>
                        @error('professional_summary')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="btn btn-primary btn-sm font-bold text-xs">Save</button>
                        <button type="button" @click="editing = null" class="text-xs font-bold text-text-muted hover:text-navy cursor-pointer">Cancel</button>
                    </div>
                </form>
            </div>

            {{-- Experience --}}
            <div x-show="tab === 'experience'" x-cloak class="p-6 sm:p-8 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="text-base font-bold text-navy">Experience</h2>
                    <button type="button" x-show="editing !== 'experience'"
                            @click="editing = 'experience'"
                            class="text-xs font-bold text-accent hover:underline shrink-0 cursor-pointer">Edit</button>
                </div>
                <dl x-show="editing !== 'experience'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ([
                        'Current title' => $profile?->current_title,
                        'Years of experience' => $profile?->years_experience !== null ? $profile->years_experience.' '.Str::plural('year', $profile->years_experience) : null,
                        'Notice period' => $profile?->notice_period,
                        'Highest qualification' => $profile?->qualification,
                    ] as $label => $value)
                        <div class="rounded-xl border border-border p-3.5">
                            <dt class="text-xs font-bold uppercase tracking-wider text-text-secondary">{{ $label }}</dt>
                            <dd class="text-sm font-semibold {{ filled($value) ? 'text-navy' : 'text-text-muted italic' }} mt-1">{{ filled($value) ? $value : 'Not set' }}</dd>
                        </div>
                    @endforeach
                </dl>
                <form method="POST" action="{{ route('seeker.profile.section', 'experience') }}"
                      x-show="editing === 'experience'" x-cloak class="space-y-4">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="current_title" class="block text-xs font-bold text-navy mb-2">Your trade or job title</label>
                        <input type="text" id="current_title" name="current_title" value="{{ old('current_title', $profile?->current_title) }}"
                               placeholder="Site Electrician" class="form-input">
                        @error('current_title')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="years_experience" class="block text-xs font-bold text-navy mb-2">Years of experience</label>
                        <input type="number" id="years_experience" name="years_experience" value="{{ old('years_experience', $profile?->years_experience) }}"
                               placeholder="5" class="form-input" min="0" max="70" step="1">
                        @error('years_experience')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="current_location" class="block text-xs font-bold text-navy mb-2">Where you are now</label>
                        <input type="text" id="current_location" name="current_location" value="{{ old('current_location', $profile?->current_location) }}"
                               placeholder="Singapore" class="form-input">
                        @error('current_location')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="notice_period" class="block text-xs font-bold text-navy mb-2">Notice period</label>
                        <input type="text" id="notice_period" name="notice_period" value="{{ old('notice_period', $profile?->notice_period) }}"
                               placeholder="Immediate" class="form-input">
                        @error('notice_period')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="qualification" class="block text-xs font-bold text-navy mb-2">Highest qualification</label>
                        <input type="text" id="qualification" name="qualification" value="{{ old('qualification', $profile?->qualification) }}"
                               placeholder="ITI Electrician" class="form-input">
                        @error('qualification')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>
                    </div>
                    <p class="text-[11px] text-text-muted leading-relaxed">Your title, years and location are three of the four things we score a vacancy against. Leaving them blank is why a match list comes back empty.</p>
                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="btn btn-primary btn-sm font-bold text-xs">Save</button>
                        <button type="button" @click="editing = null" class="text-xs font-bold text-text-muted hover:text-navy cursor-pointer">Cancel</button>
                    </div>
                </form>
            </div>

            {{-- Skills --}}
            <div x-show="tab === 'skills'" x-cloak class="p-6 sm:p-8 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-navy">Skills</h2>
                        <p class="text-xs text-text-muted mt-0.5">{{ count($skills) }} added &mdash; this is what we match on most.</p>
                    </div>
                    <button type="button" x-show="editing !== 'skills'"
                            @click="editing = 'skills'"
                            class="text-xs font-bold text-accent hover:underline shrink-0 cursor-pointer">Edit</button>
                </div>
                <div x-show="editing !== 'skills'">
                    @if (count($skills))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($skills as $skill)
                                <span class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-50 text-navy border border-slate-200">{{ $skill }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-text-muted italic">No skills yet. Without them we cannot score you against a vacancy, so your matches stay empty.</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('seeker.profile.section', 'skills') }}"
                      x-show="editing === 'skills'" x-cloak class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label for="skills" class="block text-xs font-bold text-navy mb-2">Your skills, separated by commas</label>
                        <textarea id="skills" name="skills" rows="3" class="form-input"
                                  placeholder="Electrical wiring, Conduit installation, Fault finding">{{ old('skills', implode(', ', $skills)) }}</textarea>
                        <p class="text-[11px] text-text-muted mt-2 leading-relaxed">
                            This is what we match on most. Write them the way you would say them on site &mdash; we do not need job-board language.
                        </p>
                        @error('skills')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="btn btn-primary btn-sm font-bold text-xs">Save</button>
                        <button type="button" @click="editing = null" class="text-xs font-bold text-text-muted hover:text-navy cursor-pointer">Cancel</button>
                    </div>
                </form>
            </div>

            {{-- Documents --}}
            <div x-show="tab === 'documents'" x-cloak class="p-6 sm:p-8 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="text-base font-bold text-navy">Documents</h2>
                    <a href="{{ $editUrl($sections['documents']['anchor']) }}" class="text-xs font-bold text-accent hover:underline shrink-0">Edit</a>
                </div>
                @if ($profile?->resume_file_name)
                    <div class="rounded-xl border border-border p-4 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-navy truncate">{{ $profile->resume_file_name }}</p>
                            <p class="text-xs text-text-muted">Resume</p>
                        </div>
                        @if ($profile->resume_path)
                            <a href="{{ asset($profile->resume_path) }}" target="_blank" rel="noopener" class="text-xs font-bold text-accent hover:underline shrink-0">View</a>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-text-muted italic">No documents yet.</p>
                @endif
            </div>

            {{-- Preferences --}}
            <div x-show="tab === 'preferences'" x-cloak class="p-6 sm:p-8 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="text-base font-bold text-navy">What you are looking for</h2>
                    <button type="button" x-show="editing !== 'preferences'"
                            @click="editing = 'preferences'"
                            class="text-xs font-bold text-accent hover:underline shrink-0 cursor-pointer">Edit</button>
                </div>
                <dl x-show="editing !== 'preferences'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ([
                        'Preferred locations' => $profile?->preferred_location,
                        'Expected salary' => $profile?->expected_salary > 0 ? trim(($profile->preferred_currency ?? '').' '.number_format($profile->expected_salary)) : null,
                        'Availability' => $profile?->availability,
                        'Open to relocating' => $profile?->open_to_relocate === null ? null : ($profile->open_to_relocate ? 'Yes' : 'No'),
                    ] as $label => $value)
                        <div class="rounded-xl border border-border p-3.5">
                            <dt class="text-xs font-bold uppercase tracking-wider text-text-secondary">{{ $label }}</dt>
                            <dd class="text-sm font-semibold {{ filled($value) ? 'text-navy' : 'text-text-muted italic' }} mt-1">{{ filled($value) ? $value : 'Not set' }}</dd>
                        </div>
                    @endforeach
                </dl>
                <form method="POST" action="{{ route('seeker.profile.section', 'preferences') }}"
                      x-show="editing === 'preferences'" x-cloak class="space-y-4">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="preferred_location" class="block text-xs font-bold text-navy mb-2">Where you want to work</label>
                            <input type="text" id="preferred_location" name="preferred_location"
                                   value="{{ old('preferred_location', $profile?->preferred_location) }}"
                                   placeholder="Jurong, Tuas" class="form-input">
                            @error('preferred_location')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="availability" class="block text-xs font-bold text-navy mb-2">When you can start</label>
                            <input type="text" id="availability" name="availability"
                                   value="{{ old('availability', $profile?->availability) }}"
                                   placeholder="Immediately" class="form-input">
                            @error('availability')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="expected_salary" class="block text-xs font-bold text-navy mb-2">Pay you are looking for</label>
                            <input type="number" id="expected_salary" name="expected_salary" min="0" step="1"
                                   value="{{ old('expected_salary', $profile?->expected_salary > 0 ? (int) $profile->expected_salary : null) }}"
                                   placeholder="3000" class="form-input font-mono">
                            @error('expected_salary')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="preferred_currency" class="block text-xs font-bold text-navy mb-2">Currency</label>
                            <input type="text" id="preferred_currency" name="preferred_currency" maxlength="3"
                                   value="{{ old('preferred_currency', $profile?->preferred_currency) }}"
                                   placeholder="SGD" class="form-input font-mono">
                            @error('preferred_currency')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <p class="text-[11px] text-text-muted leading-relaxed">
                        Pay is never shown to employers as a demand &mdash; it only stops us matching you to work that pays below what you can accept.
                    </p>
                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="btn btn-primary btn-sm font-bold text-xs">Save</button>
                        <button type="button" @click="editing = null" class="text-xs font-bold text-text-muted hover:text-navy cursor-pointer">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        {{--
            TickBig also carry Achievements, Testimonials and Ratings tabs. Left
            out deliberately: we have nothing real to put in them yet, and an
            empty social-proof tab is the fabricated-content failure this project
            keeps repeating. They ship with the employer-rates-candidate flow,
            not before it.
        --}}

        <div class="text-center">
            <a href="{{ route('seeker.resume.matches') }}" class="text-xs font-bold text-accent hover:underline">See the jobs that fit you &rarr;</a>
        </div>
    </div>
</x-seeker-sidebar>
