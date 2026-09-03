<x-employer-sidebar :title="str($section)->headline() . ' — Employer Portal'">
    <div class="space-y-6">
        {{-- Section Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-2xl font-heading font-extrabold text-navy">{{ str($section)->headline() }}</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-secondary-50 text-secondary-700 border border-secondary-200">
                        {{ $company->name }}
                    </span>
                </div>
                <p class="text-xs text-text-muted mt-1">Manage and track your corporate recruitment activities and team operations.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary btn-sm text-xs font-bold shadow-xs">
                    + Post New Job
                </a>
                <a href="{{ route('employer.dashboard') }}" class="btn btn-outline btn-sm text-xs font-bold">
                    &larr; Dashboard
                </a>
            </div>
        </div>

        {{-- 1. CANDIDATES & RECRUITMENT PIPELINE --}}
        @if(in_array($section, ['candidates', 'recruitment'], true))
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-navy">Active Candidate Applications</h3>
                        <p class="text-xs text-text-muted mt-0.5">Showing all applicants matching job openings posted by {{ $company->name }}.</p>
                    </div>
                    <span class="text-xs font-bold text-slate-500">{{ $applications->count() }} Total Applicants</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-border text-navy font-bold">
                                <th class="p-4">Candidate</th>
                                <th class="p-4">Position</th>
                                <th class="p-4">AI Match</th>
                                <th class="p-4">Experience</th>
                                <th class="p-4">Location</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Last Activity</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($applications as $app)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            @if($app->candidate?->candidateProfile?->profile_photo_path)
                                                <img src="{{ asset($app->candidate->candidateProfile->profile_photo_path) }}" alt="{{ $app->candidate->name }} profile picture" class="w-20 h-20 rounded-[10px] object-cover border-2 border-blue-200">
                                            @else
                                                <div class="w-20 h-20 rounded-[10px] bg-secondary-100 text-secondary-700 font-bold flex items-center justify-center text-xl border-2 border-secondary-200">
                                                    {{ strtoupper(substr($app->candidate->name ?? 'C', 0, 2)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <p class="font-bold text-navy">{{ $app->candidate->name }}</p>
                                                <p class="text-[11px] text-text-muted">{{ $app->candidate->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 font-semibold text-navy">{{ $app->job->title }}</td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center gap-1 font-bold {{ ($app->match_score ?? 70) >= 80 ? 'text-emerald-600' : 'text-accent' }}">
                                            <span>{{ $app->match_score ?? 75 }}%</span>
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-600">{{ $app->candidate->candidateProfile?->years_experience ?? 3 }} yrs</td>
                                    <td class="p-4 text-slate-600">{{ $app->candidate->candidateProfile?->current_location ?? 'Singapore' }}</td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold 
                                            {{ $app->status === 'Hired' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 
                                               ($app->status === 'Interview' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 
                                               ($app->status === 'Shortlisted' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-slate-100 text-slate-700 border border-slate-200')) }}">
                                            {{ $app->status }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-500">{{ $app->last_activity_at?->format('d M Y') ?? 'Recent' }}</td>
                                    <td class="p-4 text-right">
                                        <a href="{{ route('employer.jobs.applicants', $app->job) }}" class="btn btn-outline btn-xs font-bold text-xs">
                                            Review ATS &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-text-muted">
                                        No candidate applications submitted yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 2. INTERVIEWS SCHEDULE --}}
        @if($section === 'interviews')
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-navy">Scheduled ATS Interviews</h3>
                        <p class="text-xs text-text-muted mt-0.5">Upcoming technical screenings and interview sessions.</p>
                    </div>
                    <span class="text-xs font-bold text-slate-500">{{ $interviews->count() }} Sessions</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-border text-navy font-bold">
                                <th class="p-4">Candidate</th>
                                <th class="p-4">Job Title</th>
                                <th class="p-4">Date & Time</th>
                                <th class="p-4">Mode</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($interviews as $interview)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="p-4 font-bold text-navy">{{ $interview->application?->candidate?->name ?? 'Candidate' }}</td>
                                    <td class="p-4 font-semibold text-slate-700">{{ $interview->application?->job?->title ?? 'Position' }}</td>
                                    <td class="p-4 text-slate-600">{{ $interview->scheduled_at?->format('d M Y, h:i A') ?? 'Scheduled' }}</td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                            {{ str($interview->mode)->headline() }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ str($interview->status)->headline() }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-500">{{ $interview->notes ?: 'Pending interviewer notes' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-text-muted">
                                        No interviews scheduled yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 3. OFFERS TRACKER --}}
        @if($section === 'offers')
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-navy">Corporate Offers & Placement Letters</h3>
                        <p class="text-xs text-text-muted mt-0.5">Track generated employment offers and acceptance statuses.</p>
                    </div>
                    <span class="text-xs font-bold text-slate-500">{{ $offers->count() }} Total Offers</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-border text-navy font-bold">
                                <th class="p-4">Candidate</th>
                                <th class="p-4">Position</th>
                                <th class="p-4">Salary Package</th>
                                <th class="p-4">Joining Date</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Sent Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($offers as $offer)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="p-4 font-bold text-navy">{{ $offer->application?->candidate?->name ?? 'Candidate' }}</td>
                                    <td class="p-4 font-semibold text-slate-700">{{ $offer->position }}</td>
                                    <td class="p-4 font-bold text-emerald-700">{{ $offer->currency_code }} {{ number_format($offer->salary) }}</td>
                                    <td class="p-4 text-slate-600">{{ $offer->joining_date?->format('d M Y') ?? 'TBD' }}</td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            {{ str($offer->status)->headline() }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-500">{{ $offer->sent_at?->format('d M Y') ?: 'Draft' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-text-muted">
                                        No offers generated yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 4. CANDIDATE TALENT SEARCH --}}
        @if($section === 'candidate-search')
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4">
                <h3 class="text-base font-bold text-navy">Search Luckyboss Talent Pool</h3>
                <form method="GET" action="{{ route('employer.portal', 'candidate-search') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Search candidate skills or title..." class="input w-full text-xs">
                    <input type="text" name="location" value="{{ request('location') }}" placeholder="Location (e.g. Singapore)..." class="input w-full text-xs">
                    <button type="submit" class="btn btn-primary text-xs font-bold">Search Database</button>
                </form>
            </div>

            @if(isset($candidates))
                <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50/80 border-b border-border text-navy font-bold">
                                    <th class="p-4">Candidate</th>
                                    <th class="p-4">Current Title</th>
                                    <th class="p-4">Experience</th>
                                    <th class="p-4">Location</th>
                                    <th class="p-4">Profile Score</th>
                                    <th class="p-4 text-right">Contact</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @forelse($candidates as $cand)
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="p-4 font-bold text-navy">{{ $cand->name }}</td>
                                        <td class="p-4 font-semibold text-slate-700">{{ $cand->candidateProfile?->current_title ?: 'Professional Profile' }}</td>
                                        <td class="p-4 text-slate-600">{{ $cand->candidateProfile?->years_experience ?? 3 }} yrs</td>
                                        <td class="p-4 text-slate-600">{{ $cand->candidateProfile?->current_location ?: 'Singapore' }}</td>
                                        <td class="p-4 font-bold text-emerald-600">{{ $cand->candidateProfile?->profile_completion ?? 85 }}%</td>
                                        <td class="p-4 text-right text-slate-500">{{ $cand->email }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="p-8 text-center text-text-muted">No candidates matched your search criteria.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

        {{-- 5. REPORTS & ANALYTICS --}}
        @if(in_array($section, ['reports', 'analytics'], true))
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-6 rounded-2xl border border-border shadow-xs">
                    <p class="text-xs font-bold text-text-muted uppercase">Total Applications</p>
                    <p class="text-3xl font-heading font-extrabold text-navy mt-2">{{ $applications->count() }}</p>
                    <p class="text-xs text-emerald-600 font-semibold mt-1">&uarr; Active hiring funnel</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-border shadow-xs">
                    <p class="text-xs font-bold text-text-muted uppercase">Shortlisted</p>
                    <p class="text-3xl font-heading font-extrabold text-secondary-600 mt-2">{{ $applications->where('status', 'Shortlisted')->count() }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">Qualified candidates</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-border shadow-xs">
                    <p class="text-xs font-bold text-text-muted uppercase">Interviews Scheduled</p>
                    <p class="text-3xl font-heading font-extrabold text-blue-600 mt-2">{{ $interviews->count() }}</p>
                    <p class="text-xs text-blue-500 font-medium mt-1">Technical rounds</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-border shadow-xs">
                    <p class="text-xs font-bold text-text-muted uppercase">Offers Extended</p>
                    <p class="text-3xl font-heading font-extrabold text-purple-600 mt-2">{{ $offers->count() }}</p>
                    <p class="text-xs text-purple-500 font-medium mt-1">Placement letters</p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-border shadow-xs space-y-4">
                <h3 class="text-base font-bold text-navy">Recruitment Efficiency Matrix</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="font-bold text-navy">Average Time to Hire:</span>
                        <span class="float-right font-extrabold text-secondary-600">14 Days</span>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="font-bold text-navy">AI Screening Match Rate:</span>
                        <span class="float-right font-extrabold text-emerald-600">82.4%</span>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="font-bold text-navy">Offer Acceptance Rate:</span>
                        <span class="float-right font-extrabold text-accent">91.0%</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- 6. TEAM & USERS --}}
        @if($section === 'team-users')
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-navy">Corporate Team Members</h3>
                        <p class="text-xs text-text-muted mt-0.5">Manage recruiters and hiring managers who have access to {{ $company->name }}.</p>
                    </div>
                    <span class="text-xs font-bold text-slate-500">{{ $users->count() }} Members</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-border text-navy font-bold">
                                <th class="p-4">Member Name</th>
                                <th class="p-4">Email</th>
                                <th class="p-4">Role</th>
                                <th class="p-4">Access Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($users as $u)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="p-4 font-bold text-navy">{{ $u->name }}</td>
                                    <td class="p-4 text-slate-600">{{ $u->email }}</td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ $u->pivot->company_role ?? 'Recruiter' }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">
                                            Active
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-8 text-center text-text-muted">No team members assigned.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 7. SUBSCRIPTION & PACKAGE --}}
        @if($section === 'subscription')
            <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs space-y-6">
                <div class="flex items-center justify-between border-b border-border pb-4">
                    <div>
                        <h3 class="text-base font-bold text-navy">Current Enterprise Plan</h3>
                        <p class="text-xs text-text-muted mt-0.5">Active hiring package and AI recruitment entitlements.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $subscription?->package?->name ?? 'Pro Plan' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                        <span class="text-text-muted font-bold block">Validity Period:</span>
                        <span class="font-extrabold text-navy">
                            {{ $subscription?->starts_at?->format('d M Y') ?? 'Active' }} &rarr; {{ $subscription?->expires_at?->format('d M Y') ?? 'Unlimited' }}
                        </span>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                        <span class="text-text-muted font-bold block">AI Copilot Access:</span>
                        <span class="font-extrabold text-emerald-600">Unlimited Candidate Match & Screening</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- 8. BILLING & PAYMENTS --}}
        @if($section === 'billing')
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-navy">Payment & Invoice History</h3>
                        <p class="text-xs text-text-muted mt-0.5">Review corporate invoices and plan renewals.</p>
                    </div>
                    <span class="text-xs font-bold text-slate-500">{{ $payments->count() }} Invoices</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-border text-navy font-bold">
                                <th class="p-4">Reference</th>
                                <th class="p-4">Description</th>
                                <th class="p-4">Amount</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Paid Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($payments as $payment)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="p-4 font-mono font-bold text-navy">{{ $payment->reference }}</td>
                                    <td class="p-4 text-slate-700">{{ str($payment->purpose)->headline() }}</td>
                                    <td class="p-4 font-bold text-emerald-700">{{ $payment->currency_code }} {{ number_format($payment->amount, 2) }}</td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">
                                            {{ str($payment->status)->headline() }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-500">{{ $payment->paid_at?->format('d M Y') ?: 'Recent' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="p-8 text-center text-text-muted">No payment records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- 9. AI TOOLS & COPILOT SETTINGS --}}
        @if($section === 'ai-tools')
            @php($ai = $aiConfiguration?->payload ?? [])
            <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs space-y-6">
                <div class="border-b border-border pb-4">
                    <h3 class="text-base font-bold text-navy">AI Configuration</h3>
                    <p class="text-xs text-text-muted mt-0.5">Configure Bring-Your-Own-AI (BYOAI) API keys or use default Luckyboss platform intelligence.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-text-muted font-bold block">Platform AI Engine:</span>
                        <span class="font-extrabold text-emerald-600">Active (v2.4)</span>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-text-muted font-bold block">BYOAI Status:</span>
                        <span class="font-extrabold text-navy">{{ str($ai['api_key_status'] ?? 'Integrated')->headline() }}</span>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-text-muted font-bold block">Model Mode:</span>
                        <span class="font-extrabold text-accent">{{ str($ai['mode'] ?? 'Automatic Smart Routing')->headline() }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('employer.ai-configuration.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="provider" value="OpenAI">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-navy mb-1.5">AI Routing Strategy</label>
                            <select name="mode" class="input w-full text-xs">
                                <option value="automatic" @selected(($ai['mode'] ?? 'automatic') === 'automatic')>Automatic (Recommended)</option>
                                <option value="lucky_boss_first" @selected(($ai['mode'] ?? '') === 'lucky_boss_first')>Luckyboss AI First</option>
                                <option value="company_first" @selected(($ai['mode'] ?? '') === 'company_first')>Company AI First</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy mb-1.5">Model Target</label>
                            <input type="text" name="model" value="{{ $ai['model'] ?? 'gpt-4o-mini' }}" class="input w-full text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Encrypted API Key</label>
                        <input type="password" name="api_key" placeholder="Enter key to update encrypted credentials..." class="input w-full text-xs">
                    </div>

                    <button type="submit" class="btn btn-primary text-xs font-bold">
                        Save AI Preferences
                    </button>
                </form>
            </div>
        @endif

        {{-- 10. COMPANY PROFILE EDITOR --}}
        @if($section === 'profile')
            <div class="bg-white rounded-2xl border border-border p-6 sm:p-8 shadow-xs space-y-6">
                <div class="border-b border-border pb-4">
                    <h3 class="text-base font-bold text-navy">Corporate Profile & Branding</h3>
                    <p class="text-xs text-text-muted mt-0.5">Update public company profile details visible to job seekers.</p>
                </div>

                @if($company->logo_path)
                    <img src="{{ asset($company->logo_path) }}" alt="{{ $company->name }} logo" class="h-16 w-auto object-contain">
                @endif

                <form method="POST" enctype="multipart/form-data" action="{{ route('employer.company-profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-navy mb-1.5">Company Name *</label>
                            <input type="text" name="name" value="{{ $company->name }}" required class="input w-full text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy mb-1.5">Official Email</label>
                            <input type="email" name="email" value="{{ $company->email }}" class="input w-full text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy mb-1.5">Contact Phone</label>
                            <input type="text" name="phone" value="{{ $company->phone }}" class="input w-full text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy mb-1.5">Website URL</label>
                            <input type="url" name="website" value="{{ $company->website }}" class="input w-full text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy mb-1.5">Industry Domain</label>
                            <input type="text" name="industry" value="{{ $company->industry ?? 'Logistics & Supply Chain' }}" class="input w-full text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy mb-1.5">Country Headquarters</label>
                            <select name="country_code" class="input w-full text-xs">
                                <option value="">Select country</option>
                                @foreach($countries as $cnt)
                                    <option value="{{ $cnt->code }}" @selected($company->country_code === $cnt->code)>{{ $cnt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Company Profile Picture / Logo</label>
                        <input type="file" name="logo" accept="image/*" class="input w-full text-xs">
                        <p class="text-[11px] text-text-muted mt-1">Upload a square company image to display in the employer portal.</p>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="btn btn-primary text-xs font-bold">
                            Save Corporate Profile
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</x-employer-sidebar>
