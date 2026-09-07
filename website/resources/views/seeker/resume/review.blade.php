<x-seeker-sidebar title="Check Your Details">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10 space-y-6"
         x-data="resumeReview({{ Illuminate\Support\Js::from($skills) }})">

        <div class="space-y-2">
            <h1 class="text-2xl font-heading font-extrabold text-navy">
                {{ $fromParse ? 'Check what we read' : 'Tell us about your work' }}
            </h1>
            <p class="text-sm text-text-secondary leading-relaxed">
                @if ($fromParse)
                    These came from your resume, so they may not be exact. Correct anything that&rsquo;s
                    wrong &mdash; nothing is saved to your profile until you press the button at the bottom.
                @else
                    The more you tell us, the better we can match you. You can change any of it later.
                @endif
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="rounded-2xl border border-accent/30 bg-accent/5 px-5 py-4 text-sm font-semibold text-navy">{{ session('info') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 space-y-1">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        @if ($resume && ($resume['stored'] ?? false))
            <div class="rounded-2xl border border-border bg-white p-4 flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 grid place-items-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-navy truncate">{{ $resume['file_name'] }}</p>
                    <p class="text-xs text-text-muted">Saved to your profile and sent with your applications.</p>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('seeker.resume.confirm') }}" class="space-y-6">
            @csrf

            {{-- Who you are --}}
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4">
                <h2 class="text-base font-bold text-navy border-b border-border pb-3">Your details</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Full name</label>
                        <input id="name" name="name" type="text" required maxlength="120" value="{{ old('name', $values['name']) }}" class="form-input">
                    </div>
                    <div>
                        <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Phone</label>
                        <input id="phone" name="phone" type="text" maxlength="32" value="{{ old('phone', $values['phone']) }}" class="form-input">
                    </div>
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Email</label>
                        <input id="email" name="email" type="email" required value="{{ old('email', $values['email']) }}" class="form-input">
                    </div>
                    <div>
                        <label for="current_location" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Where you can work</label>
                        <input id="current_location" name="current_location" type="text" maxlength="180" value="{{ old('current_location', $values['current_location']) }}" class="form-input" placeholder="Singapore">
                    </div>
                </div>
            </div>

            {{-- Your work --}}
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4">
                <h2 class="text-base font-bold text-navy border-b border-border pb-3">Your work</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="current_title" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Your trade or job title</label>
                        <input id="current_title" name="current_title" type="text" maxlength="180" value="{{ old('current_title', $values['current_title']) }}" class="form-input" placeholder="Electrician">
                    </div>
                    <div>
                        <label for="years_experience" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Years of experience</label>
                        <input id="years_experience" name="years_experience" type="number" min="0" max="60" value="{{ old('years_experience', $values['years_experience']) }}" class="form-input font-mono">
                    </div>
                </div>

                <div>
                    <label for="professional_summary" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">About you</label>
                    <textarea id="professional_summary" name="professional_summary" rows="3" maxlength="5000" class="form-input" placeholder="A couple of lines about the work you do.">{{ old('professional_summary', $values['professional_summary']) }}</textarea>
                </div>
            </div>

            {{-- Skills. The single most important field for matching, so it gets
                 its own card and says so. --}}
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4" @click.away="showSuggestions = false">
                <div class="border-b border-border pb-3">
                    <h2 class="text-base font-bold text-navy">Your skills</h2>
                    <p class="text-xs text-text-muted mt-0.5">This is what we match on most. Add everything you can actually do.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 min-h-[44px] p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <template x-for="(skill, i) in skills" :key="i">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-navy border border-slate-300">
                            <span x-text="skill"></span>
                            <button type="button" @click="remove(i)" class="text-slate-400 hover:text-rose-600 cursor-pointer text-sm font-bold leading-none" title="Remove">&times;</button>
                        </span>
                    </template>
                    <span x-show="skills.length === 0" class="text-xs text-slate-400 italic">No skills yet. Add them below.</span>
                </div>

                <div class="relative">
                    <div class="flex items-center gap-2">
                        <input type="text"
                               x-model="query"
                               @input.debounce.250ms="search()"
                               @keydown.enter.prevent="add()"
                               placeholder="Type a skill, e.g. Wiring, Forklift, Welding"
                               class="form-input flex-1">
                        <button type="button" @click="add()" class="btn btn-outline btn-sm cursor-pointer shrink-0">Add</button>
                    </div>

                    <div x-show="showSuggestions && suggestions.length" x-cloak
                         class="absolute left-0 right-0 mt-1 bg-white rounded-xl shadow-xl border border-border z-30 max-h-56 overflow-y-auto divide-y divide-border">
                        <template x-for="s in suggestions" :key="s">
                            <div @click="add(s)" class="p-2.5 text-xs font-semibold text-navy hover:bg-blue-50 hover:text-accent cursor-pointer" x-text="s"></div>
                        </template>
                    </div>
                </div>

                <input type="hidden" name="skills" :value="JSON.stringify(skills)">

                {{-- Without scripting the chips above cannot be edited, so the
                     same value is offered as plain comma-separated text. The
                     controller accepts either. --}}
                <noscript>
                    <label for="skills-plain" class="block text-xs font-bold text-navy mb-1.5">Skills, separated by commas</label>
                    <input id="skills-plain" name="skills" type="text" value="{{ implode(', ', $skills) }}" class="form-input">
                </noscript>
            </div>

            {{-- Education, folded away: it matters least for the trades we serve,
                 and a long form is the thing that loses people. --}}
            <details class="bg-white rounded-2xl border border-border p-6 shadow-xs">
                <summary class="text-base font-bold text-navy cursor-pointer">Education <span class="text-xs font-normal text-text-muted">(optional)</span></summary>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label for="qualification" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Highest qualification</label>
                        <input id="qualification" name="qualification" type="text" maxlength="80" value="{{ old('qualification', $values['qualification']) }}" class="form-input">
                    </div>
                    <div>
                        <label for="course" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Course</label>
                        <input id="course" name="course" type="text" maxlength="180" value="{{ old('course', $values['course']) }}" class="form-input">
                    </div>
                    <div>
                        <label for="passing_year" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Year finished</label>
                        <input id="passing_year" name="passing_year" type="text" maxlength="10" value="{{ old('passing_year', $values['passing_year']) }}" class="form-input font-mono">
                    </div>
                </div>
            </details>

            <div class="bg-white p-5 rounded-2xl border border-border shadow-md flex flex-wrap items-center justify-between gap-4">
                <p class="text-xs text-text-muted">
                    {{ $fromParse ? 'Nothing has been saved yet. This is the step that saves it.' : 'You can change any of this later from your profile.' }}
                </p>
                <button type="submit" class="btn btn-primary btn-lg shadow-md cursor-pointer">
                    Save &amp; see my matches
                </button>
            </div>
        </form>
    </div>

    {{-- Alpine.data() is unavailable in this bundle (see CLAUDE.md): registering
         a component the documented way throws and kills Alpine for the whole
         page. A global factory is resolved by x-data and works. --}}
    <script>
        window.resumeReview = function (initialSkills) {
            return {
                skills: Array.isArray(initialSkills) ? initialSkills.slice() : [],
                query: '',
                suggestions: [],
                showSuggestions: false,

                add(value) {
                    const skill = (value || this.query || '').trim();
                    if (!skill) return;
                    if (!this.skills.some(s => s.toLowerCase() === skill.toLowerCase())) {
                        this.skills.push(skill);
                    }
                    this.query = '';
                    this.suggestions = [];
                    this.showSuggestions = false;
                },

                remove(index) {
                    this.skills.splice(index, 1);
                },

                search() {
                    const q = this.query.trim();
                    if (q.length < 2) { this.suggestions = []; this.showSuggestions = false; return; }

                    fetch('/api/v1/skills/search?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                        .then(r => r.ok ? r.json() : null)
                        .then(payload => {
                            const rows = (payload && (payload.data || payload)) || [];
                            this.suggestions = rows
                                .map(r => typeof r === 'string' ? r : (r.name || r.skill || ''))
                                .filter(Boolean)
                                .filter(s => !this.skills.some(x => x.toLowerCase() === s.toLowerCase()))
                                .slice(0, 8);
                            this.showSuggestions = this.suggestions.length > 0;
                        })
                        // A failed lookup must not block typing: the free-text
                        // Add button still works with no suggestions at all.
                        .catch(() => { this.suggestions = []; this.showSuggestions = false; });
                },
            };
        };
    </script>
</x-seeker-sidebar>
