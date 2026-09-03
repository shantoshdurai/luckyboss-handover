<x-layouts.app :title="$job->title . ' | ' . ($job->company->name ?? 'Luckyboss')" :description="Str::limit(strip_tags($job->description), 155)" :image="asset($job->image_path)" :imageAlt="$job->title">

{{-- Google for Jobs. Without this block every vacancy here is invisible to the
     largest free source of candidate traffic in all three markets. --}}
<script type="application/ld+json">@json(app(\App\Services\JobPostingSchema::class)->for($job), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    <article class="bg-slate-50 min-h-screen">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
            <a href="{{ route('jobs.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-navy hover:text-accent transition-colors mb-6">&larr; Back to Jobs</a>

            <div class="bg-white rounded-3xl border border-border shadow-xs overflow-hidden">
                <div class="job-detail-main-grid">
                    <div class="p-6 sm:p-10">
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            @if($job->jobCategory)
                                <span class="px-3 py-1 rounded-full bg-blue-50 text-accent text-xs font-bold">{{ $job->jobCategory->name }}</span>
                            @endif
                            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold">{{ $job->job_type }}</span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-heading font-extrabold text-navy">{{ $job->title }}</h1>
                        <p class="mt-3 text-base font-semibold text-slate-600">{{ $job->company->name ?? 'Verified Regional Partner' }} &bull; {{ $job->location ?: $job->country_code }}</p>

                        <div class="mt-8 border-y border-border divide-y divide-border">
                            <div class="flex items-center justify-between gap-4 py-4"><span class="text-xs text-slate-500">Work mode</span><strong class="text-sm text-navy">{{ str($job->work_mode)->headline() }}</strong></div>
                            <div class="flex items-center justify-between gap-4 py-4"><span class="text-xs text-slate-500">Experience</span><strong class="text-sm text-navy">{{ $job->experience_min ?? 0 }}-{{ $job->experience_max ?? 'Any' }} years</strong></div>
                            <div class="flex items-center justify-between gap-4 py-4"><span class="text-xs text-slate-500">Open vacancies</span><strong class="text-sm text-navy">{{ $job->vacancies }}</strong></div>
                            <div class="flex items-center justify-between gap-4 py-4"><span class="text-xs text-slate-500">Salary</span><strong class="text-sm text-navy">{{ $job->salary_visible && $job->salary_min ? $job->currency_code . ' ' . number_format($job->salary_min) : 'Competitive' }}</strong></div>
                        </div>
                    </div>
                    <div class="p-6 sm:p-10 bg-slate-50 border-t lg:border-t-0 lg:border-l border-border">
                        <img src="{{ asset($job->image_path) }}" alt="{{ $job->title }}" class="w-full aspect-[4/3] rounded-2xl object-cover border border-border">
                        <div class="mt-6 flex items-center gap-4">
                            <div class="relative h-24 w-24 shrink-0">
                                <svg class="h-24 w-24 -rotate-90" viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="42" fill="none" stroke="#dbe4ef" stroke-width="8"/><circle cx="50" cy="50" r="42" fill="none" stroke="#18a66a" stroke-width="8" stroke-linecap="round" stroke-dasharray="{{ $matchScore ?? 0 }} 100" pathLength="100"/></svg>
                                <span class="absolute inset-0 grid place-items-center text-xl font-heading font-extrabold text-navy">{{ $matchScore !== null ? $matchScore . '%' : '--' }}</span>
                            </div>
                            <div><p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Job match</p><p class="mt-1 text-sm font-semibold text-navy">{{ $matchScore !== null ? 'Based on your profile' : 'Sign in as a candidate to see your match' }}</p></div>
                        </div>
                    </div>
                </div>
                <section class="border-t border-border p-6 sm:p-10">
                    <h2 class="text-xl font-bold text-navy mb-3">Job Description</h2>
                    <p class="text-sm leading-7 text-slate-600 whitespace-pre-line">{{ $job->description }}</p>
                    <div class="mt-6 rounded-2xl bg-slate-50 border border-border p-4">
                        <h2 class="text-sm font-bold text-navy">About the employer</h2>
                        <p class="text-sm text-slate-600 mt-1">{{ $job->company->name ?? 'Verified Regional Partner' }} is hiring for this opportunity through Luckyboss Employment Agency Pte. Ltd.</p>
                    </div>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        @auth
                            @if(auth()->user()->hasRole('job-seeker'))
                                @if($application)
                                    <a href="{{ route('seeker.dashboard', ['tab' => 'applications']) }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-3 font-bold">&#10003; Already Applied <span class="text-xs font-semibold">View application</span></a>
                                @else
                                    <div x-data="{ confirmApply: false }">
                                        <form x-ref="applyForm" method="POST" action="{{ route('seeker.jobs.apply', $job) }}">@csrf<button type="button" @click="confirmApply = true" class="btn btn-primary px-6 py-3 font-bold">Apply for this job</button></form>
                                        <div x-show="confirmApply" x-cloak x-transition.opacity class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-navy/55 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="confirm-application-title">
                                            <div @click.away="confirmApply = false" class="w-full max-w-sm rounded-2xl bg-white border border-border shadow-2xl p-6">
                                                <h2 id="confirm-application-title" class="text-lg font-heading font-extrabold text-navy">Confirm application</h2>
                                                <p class="mt-2 text-sm leading-6 text-slate-600">Apply for <strong class="text-navy">{{ $job->title }}</strong> at {{ $job->company->name ?? 'this employer' }}?</p>
                                                <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2"><button type="button" @click="confirmApply = false" class="btn btn-outline text-xs font-bold">Review again</button><button type="button" @click="$refs.applyForm.submit()" class="btn btn-primary text-xs font-bold">Yes, apply now</button></div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary px-6 py-3 font-bold">Sign in to apply</a>
                        @endauth
                        <div class="flex items-center gap-2" aria-label="Share this job"><span class="text-xs font-bold text-slate-500">Share</span><a class="w-10 h-10 rounded-xl border border-border text-navy grid place-items-center hover:bg-blue-50" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" aria-label="Share on Facebook">f</a><a class="w-10 h-10 rounded-xl border border-border text-navy grid place-items-center hover:bg-blue-50" target="_blank" rel="noopener" href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}" aria-label="Share on LinkedIn">in</a><a class="w-10 h-10 rounded-xl border border-border text-navy grid place-items-center hover:bg-emerald-50" target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode($job->title . ' - ' . url()->current()) }}" aria-label="Share on WhatsApp">wa</a><button type="button" class="w-10 h-10 rounded-xl border border-border text-navy grid place-items-center hover:bg-slate-100" onclick="navigator.clipboard.writeText(window.location.href)" aria-label="Copy job link">&#128279;</button></div>
                    </div>
                </section>
            </div>
        </div>
    </article>
</x-layouts.app>