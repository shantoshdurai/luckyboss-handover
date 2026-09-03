<x-employer-sidebar :title="$job->title . ' — Applicants'">
    <div class="space-y-6">
        {{-- Top Navigation & Summary --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-xs">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-text-secondary mb-1">
                    <a href="{{ route('employer.jobs.index') }}" class="hover:text-accent flex items-center gap-1 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        <span>Back to My Jobs</span>
                    </a>
                    <span>•</span>
                    <span class="text-secondary-600 font-bold">{{ $job->location }}</span>
                </div>
                <h2 class="text-2xl font-heading font-extrabold text-navy">{{ $job->title }}</h2>
                <p class="text-xs text-text-muted mt-1">
                    {{ $applications->count() }} Total {{ Str::plural('Applicant', $applications->count()) }} • {{ $job->jobCategory->name ?? 'General' }}
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('employer.jobs.edit', $job) }}" class="btn btn-outline btn-sm">
                    Edit Position
                </a>
                <a href="{{ route('employer.portal', 'candidate-search') }}" class="btn btn-primary btn-sm">
                    Source Talent
                </a>
            </div>
        </div>

        {{-- Applicants List / Pipeline --}}
        <div class="space-y-4">
            @forelse($applications as $application)
                @php
                    $candidate = $application->candidate;
                    $profile = $candidate->candidateProfile;
                @endphp
                <div class="bg-white rounded-2xl border border-border hover:border-accent/50 shadow-xs hover:shadow-md transition-all p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        {{-- Candidate Info --}}
                        <div class="flex items-start gap-4 min-w-0">
                            @if($profile?->profile_photo_path)
                                <img src="{{ asset($profile->profile_photo_path) }}" alt="{{ $candidate->name }} profile picture" class="w-20 h-20 shrink-0 rounded-[10px] object-cover border-2 border-blue-200" loading="lazy">
                            @else
                                <div class="w-20 h-20 shrink-0 rounded-[10px] bg-blue-100 text-accent flex items-center justify-center text-xl font-bold border-2 border-blue-200">
                                    {{ strtoupper(substr($candidate->name ?? 'C', 0, 2)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-navy truncate">{{ $candidate->name }}</h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-accent border border-blue-200">
                                        {{ $application->match_score ?? 85 }}% Match
                                    </span>
                                </div>
                                <p class="text-xs text-text-secondary mt-0.5 truncate">{{ $candidate->email }} • {{ $candidate->phone ?? 'No phone' }}</p>
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-surface-sunken text-text-secondary border border-border">
                                        {{ $profile?->years_experience ?? 0 }} Years Exp.
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-surface-sunken text-text-secondary border border-border">
                                        {{ $profile?->expected_salary ? '$' . number_format($profile->expected_salary) : 'Salary Open' }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Status: {{ $application->status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Quick ATS Actions --}}
                        <div class="flex flex-wrap items-center gap-2 shrink-0 border-t lg:border-t-0 pt-4 lg:pt-0">
                            {{-- Update Status Form --}}
                            <form method="POST" action="{{ route('employer.applications.status', [$job, $application]) }}" class="flex items-center gap-1.5">
                                @csrf
                                <select name="status" class="text-xs rounded-xl border border-border bg-white px-3 py-2 font-medium focus:ring-accent">
                                    @foreach(['New', 'Viewed', 'Contacted', 'Shortlisted', 'Interview Scheduled', 'Interviewed', 'Selected', 'Offer Sent', 'Joined', 'Rejected', 'Archived'] as $st)
                                        <option value="{{ $st }}" {{ $application->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline btn-sm py-2 text-xs">Update</button>
                            </form>

                            {{-- Schedule Interview Modal/Button --}}
                            <div x-data="{ openInterview: false }">
                                <button @click="openInterview = true" type="button" class="btn btn-secondary btn-sm py-2 text-xs">
                                    Schedule Interview
                                </button>
                                
                                <div x-show="openInterview" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy/60 backdrop-blur-xs">
                                    <div @click.away="openInterview = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-border">
                                        <h3 class="text-lg font-bold text-navy mb-4">Schedule Interview for {{ $candidate->name }}</h3>
                                        <form method="POST" action="{{ route('employer.applications.interview', [$job, $application]) }}" class="space-y-4">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-bold text-navy mb-1">Date & Time</label>
                                                <input type="datetime-local" name="scheduled_at" required class="w-full rounded-xl border border-border text-xs p-2.5">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-navy mb-1">Interview Mode</label>
                                                <select name="mode" class="w-full rounded-xl border border-border text-xs p-2.5">
                                                    <option value="Google Meet">Google Meet</option>
                                                    <option value="Zoom">Zoom Video</option>
                                                    <option value="On-Site">In-Person / On-Site</option>
                                                    <option value="Phone Call">Phone Call</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-navy mb-1">Meeting Link / Address</label>
                                                <input type="text" name="meeting_link" placeholder="https://meet.google.com/..." class="w-full rounded-xl border border-border text-xs p-2.5">
                                            </div>
                                            <input type="hidden" name="duration_minutes" value="45">
                                            <div class="flex justify-end gap-2 pt-2">
                                                <button @click="openInterview = false" type="button" class="btn btn-outline btn-sm">Cancel</button>
                                                <button type="submit" class="btn btn-primary btn-sm">Send Invitation</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- Send Offer Modal/Button --}}
                            <div x-data="{ openOffer: false }">
                                <button @click="openOffer = true" type="button" class="btn btn-primary btn-sm py-2 text-xs">
                                    Send Offer
                                </button>

                                <div x-show="openOffer" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-navy/60 backdrop-blur-xs">
                                    <div @click.away="openOffer = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-border">
                                        <h3 class="text-lg font-bold text-navy mb-4">Extend Offer to {{ $candidate->name }}</h3>
                                        <form method="POST" action="{{ route('employer.applications.offer', [$job, $application]) }}" class="space-y-4">
                                            @csrf
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-bold text-navy mb-1">Salary</label>
                                                    <input type="number" name="salary" placeholder="4500" required class="w-full rounded-xl border border-border text-xs p-2.5">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-navy mb-1">Currency</label>
                                                    <input type="text" name="currency_code" value="{{ $job->currency_code ?? 'SGD' }}" required class="w-full rounded-xl border border-border text-xs p-2.5 uppercase">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-navy mb-1">Joining Date</label>
                                                <input type="date" name="joining_date" class="w-full rounded-xl border border-border text-xs p-2.5">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-navy mb-1">Terms / Notes</label>
                                                <textarea name="terms" rows="2" placeholder="Standard probation 3 months..." class="w-full rounded-xl border border-border text-xs p-2.5"></textarea>
                                            </div>
                                            <div class="flex justify-end gap-2 pt-2">
                                                <button @click="openOffer = false" type="button" class="btn btn-outline btn-sm">Cancel</button>
                                                <button type="submit" class="btn btn-secondary btn-sm">Dispatch Offer</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 bg-white rounded-2xl border border-border">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <h3 class="text-base font-bold text-navy">No Applicants Yet</h3>
                    <p class="text-xs text-text-muted mt-1 max-w-sm mx-auto">This job is published and receiving applications. You can also proactively search for candidates in the talent discovery pool.</p>
                    <a href="{{ route('employer.portal', 'candidate-search') }}" class="btn btn-primary btn-sm mt-4 inline-flex">
                        Source Candidates Now
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-employer-sidebar>