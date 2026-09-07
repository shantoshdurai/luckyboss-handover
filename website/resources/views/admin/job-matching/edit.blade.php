<x-admin-layout title="Job Matching & Apply All" heading="Job Matching & Apply All">
    <div class="max-w-4xl">

        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.job-matching.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Match threshold --}}
            <div class="bg-white rounded-2xl p-6 sm:p-8 border border-border shadow-sm">
                <div class="flex items-center gap-3 pb-4 mb-6 border-b border-border">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-accent flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-heading font-bold text-navy">Match Threshold</h2>
                        <p class="text-xs text-text-muted">How closely a vacancy must fit a candidate before we show it to them.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-start">
                    <div class="sm:col-span-1">
                        {{-- The unit lives in the label, not as an absolutely
                             positioned overlay: the prebuilt CSS bundle has no
                             `right-3` or `-translate-y-1/2`, so the suffix
                             landed on top of the number instead of beside it. --}}
                        <label for="minimum_match_score" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Minimum match score (%)</label>
                        <input id="minimum_match_score"
                               type="number"
                               name="minimum_match_score"
                               min="0" max="95" step="1"
                               value="{{ old('minimum_match_score', $matching['minimum_match_score']) }}"
                               class="form-input font-mono"
                               required>
                        <p class="text-[11px] text-text-muted mt-2 leading-relaxed">0&ndash;95. Set 70 for a thin local market, 80&ndash;90 where there are plenty of vacancies.</p>
                    </div>

                    <div class="sm:col-span-2 rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-2.5">
                        <p class="text-xs font-bold text-navy">What this number does</p>
                        <ul class="text-[11px] text-text-secondary space-y-1.5 leading-relaxed">
                            <li>&bull; Filters the candidate&rsquo;s recommended jobs on their dashboard and in the mobile app.</li>
                            <li>&bull; Decides which jobs <span class="font-semibold text-navy">Apply All</span> will submit to.</li>
                            <li>&bull; Applies to <span class="font-semibold text-navy">Lucky Boss vacancies only</span> &mdash; we never apply on a candidate&rsquo;s behalf to LinkedIn, Naukri or Monster.</li>
                        </ul>
                        <p class="text-[11px] text-text-muted pt-1 border-t border-slate-200 leading-relaxed">
                            Above 95% the list comes back empty for most people: a candidate&rsquo;s headline score is capped by how much of their profile we could actually compare, so a thin profile can never reach 100%.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Apply All --}}
            <div class="bg-white rounded-2xl p-6 sm:p-8 border border-border shadow-sm">
                <div class="flex items-center gap-3 pb-4 mb-6 border-b border-border">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-heading font-bold text-navy">Apply All</h2>
                        <p class="text-xs text-text-muted">One tap applies to every matching vacancy, up to the cap below.</p>
                    </div>
                </div>

                <div class="space-y-5">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input type="hidden" name="bulk_apply_enabled" value="0">
                        <input type="checkbox"
                               name="bulk_apply_enabled"
                               value="1"
                               @checked(old('bulk_apply_enabled', $matching['bulk_apply_enabled']))
                               class="mt-0.5 w-4 h-4 rounded border-slate-300 text-accent focus:ring-accent cursor-pointer">
                        <span>
                            <span class="block text-sm font-bold text-navy group-hover:text-accent">Allow candidates to use Apply All</span>
                            <span class="block text-[11px] text-text-muted mt-0.5">When off, the button disappears and candidates apply one job at a time. Applications already submitted are unaffected.</span>
                        </span>
                    </label>

                    <div class="max-w-xs">
                        <label for="bulk_apply_limit" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Maximum jobs per tap</label>
                        <input id="bulk_apply_limit"
                               type="number"
                               name="bulk_apply_limit"
                               min="1" max="100" step="1"
                               value="{{ old('bulk_apply_limit', $matching['bulk_apply_limit']) }}"
                               class="form-input font-mono"
                               required>
                        <p class="text-[11px] text-text-muted mt-2 leading-relaxed">1&ndash;100. Without a cap, one tap can put a single candidate in front of every employer at once, which reads as spam from their side.</p>
                    </div>
                </div>
            </div>

            {{-- Auto apply --}}
            <div class="rounded-2xl p-6 sm:p-8 border border-amber-200 shadow-sm bg-amber-50">
                <div class="flex items-center justify-between gap-3 pb-4 mb-6 border-b border-amber-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white border border-amber-200 text-amber-700 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-heading font-bold text-navy">Automatic Apply</h2>
                            <p class="text-xs text-text-muted">Applying on a candidate&rsquo;s behalf while they are not looking.</p>
                        </div>
                    </div>
                    <span class="shrink-0 inline-flex items-center px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-slate-200 text-slate-700 border border-slate-300">Not wired up yet</span>
                </div>

                <label class="flex items-start gap-3 cursor-pointer group">
                    <input type="hidden" name="auto_apply_enabled" value="0">
                    <input type="checkbox"
                           name="auto_apply_enabled"
                           value="1"
                           @checked(old('auto_apply_enabled', $matching['auto_apply_enabled']))
                           class="mt-0.5 w-4 h-4 rounded border-slate-300 text-accent focus:ring-accent cursor-pointer">
                    <span>
                        <span class="block text-sm font-bold text-navy group-hover:text-accent">Enable automatic apply</span>
                        <span class="block text-[11px] text-text-muted mt-0.5 leading-relaxed">
                            This switch is <span class="font-semibold text-navy">stored but not yet acted on</span> &mdash; no background job reads it, so turning it on changes nothing today. It is here so the setting exists before the feature does. Leave it off until someone has watched a run end to end.
                        </span>
                    </span>
                </label>
            </div>

            {{-- Save --}}
            <div class="bg-white p-5 rounded-2xl border border-border shadow-md flex flex-wrap items-center justify-between gap-4">
                <p class="text-xs text-text-muted">
                    Applies immediately to <span class="font-bold text-navy">{{ number_format($publishedJobs) }}</span> published {{ $publishedJobs === 1 ? 'vacancy' : 'vacancies' }}
                    and <span class="font-bold text-navy">{{ number_format($seekerCount) }}</span> {{ $seekerCount === 1 ? 'candidate' : 'candidates' }}, on web and in the mobile app.
                </p>
                <button type="submit" class="btn btn-primary btn-lg shadow-md cursor-pointer flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span>Save Matching Settings</span>
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
