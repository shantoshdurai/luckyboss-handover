<x-admin-layout :title="str($view)->headline() . ' | Luckyboss Admin'" :heading="str($view)->headline()">
    <div class="space-y-6">
        {{-- Top Navigation & Subtabs --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-xs">
            <div>
                <h2 class="text-2xl font-heading font-extrabold text-navy">Platform Notification Hub</h2>
                <p class="text-xs text-text-secondary mt-1">
                    Manage multi-channel alert delivery, event triggers, real-time push logs, and custom audio chimes.
                </p>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto">
                <a href="{{ route('admin.notifications.index', ['view' => 'all-notifications']) }}"
                   @class([
                       'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0',
                       'bg-accent text-white shadow-sm' => $view === 'all-notifications' || !$view,
                       'bg-slate-100 text-text-secondary hover:bg-slate-200' => $view !== 'all-notifications' && $view,
                   ])>
                    All Delivery Logs
                </a>
                <a href="{{ route('admin.notifications.index', ['view' => 'notification-types']) }}"
                   @class([
                       'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0',
                       'bg-accent text-white shadow-sm' => $view === 'notification-types',
                       'bg-slate-100 text-text-secondary hover:bg-slate-200' => $view !== 'notification-types',
                   ])>
                    Event Triggers
                </a>
                <a href="{{ route('admin.notifications.index', ['view' => 'notification-sounds']) }}"
                   @class([
                       'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0',
                       'bg-accent text-white shadow-sm' => $view === 'notification-sounds',
                       'bg-slate-100 text-text-secondary hover:bg-slate-200' => $view !== 'notification-sounds',
                   ])>
                    Custom Audio Chimes
                </a>
            </div>
        </div>

        {{-- VIEW 1: Audio Chimes & Sound Synthesis Engine --}}
        @if($view === 'notification-sounds')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                    $systemSounds = [
                        ['key' => 'interview_alert', 'name' => 'Interview Alert', 'role' => 'Candidate & Employer', 'desc' => 'Pleasant triple harmonic chime on interview scheduling or reschedule.', 'color' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                        ['key' => 'offer_alert', 'name' => 'Offer Extended', 'role' => 'Candidate', 'desc' => 'Celebration arpeggio played when a candidate receives a formal job offer.', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                        ['key' => 'job_match', 'name' => 'Top Job Match', 'role' => 'Job Seeker', 'desc' => 'Upbeat 3-note ascending chime when AI matches high affinity role.', 'color' => 'bg-blue-50 text-blue-700 border-blue-200'],
                        ['key' => 'applicant_alert', 'name' => 'New Applicant', 'role' => 'Employer', 'desc' => 'Prompt notification sound when a verified seeker applies.', 'color' => 'bg-amber-50 text-amber-700 border-amber-200'],
                        ['key' => 'payment_alert', 'name' => 'Payment Success', 'role' => 'All Roles', 'desc' => 'Dual register bell played upon successful subscription activation.', 'color' => 'bg-teal-50 text-teal-700 border-teal-200'],
                        ['key' => 'system_alert', 'name' => 'System & Approval', 'role' => 'Super Admin', 'desc' => 'Crisp ping alert for KYC verifications and system security notices.', 'color' => 'bg-purple-50 text-purple-700 border-purple-200'],
                    ];
                @endphp

                @foreach($systemSounds as $snd)
                    <div class="bg-white p-6 rounded-2xl border border-border shadow-xs hover:border-accent hover:shadow-md transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border {{ $snd['color'] }}">
                                    {{ $snd['role'] }}
                                </span>
                                <span class="text-[10px] font-mono text-text-muted">WebAudio API</span>
                            </div>
                            <h3 class="text-base font-bold text-navy mb-1">{{ $snd['name'] }}</h3>
                            <p class="text-xs text-text-secondary leading-relaxed">{{ $snd['desc'] }}</p>
                        </div>

                        <div class="pt-4 border-t border-border mt-4 flex items-center justify-between">
                            <span class="text-xs font-semibold text-text-muted">Key: <code class="text-navy font-mono">{{ $snd['key'] }}</code></span>
                            <button onclick="window.playLuckySound('{{ $snd['key'] }}')" type="button" class="btn btn-secondary btn-sm py-1.5 px-3 text-xs flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                <span>Preview Chime</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

        {{-- VIEW 2: Event Triggers Matrix --}}
        @elseif($view === 'notification-types')
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-6 border-b border-border">
                    <h3 class="text-lg font-bold text-navy">Configured Event Triggers</h3>
                    <p class="text-xs text-text-muted mt-0.5">Automated web, in-app push, and audio notification bindings.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-text-muted uppercase text-[10px] font-bold border-b border-border">
                            <tr>
                                <th class="py-3 px-6">Event Name</th>
                                <th class="py-3 px-6">Target Role</th>
                                <th class="py-3 px-6">Chime Sound</th>
                                <th class="py-3 px-6">Total Deliveries</th>
                                <th class="py-3 px-6 text-right">Channel Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach(['interview_alert' => 'Candidate & Employer', 'offer_alert' => 'Candidate', 'job_match' => 'Job Seeker', 'applicant_alert' => 'Employer', 'system_alert' => 'Admin', 'payment_alert' => 'All Roles'] as $t => $r)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-4 px-6 font-bold text-navy">{{ str($t)->headline() }}</td>
                                    <td class="py-4 px-6 font-medium text-text-secondary">{{ $r }}</td>
                                    <td class="py-4 px-6">
                                        <button onclick="window.playLuckySound('{{ $t }}')" type="button" class="text-xs font-bold text-accent hover:underline inline-flex items-center gap-1 cursor-pointer">
                                            <span>🔊 Play {{ $t }}</span>
                                        </button>
                                    </td>
                                    <td class="py-4 px-6 font-bold text-secondary-600">
                                        {{ \App\Models\PlatformNotification::where('type', $t)->count() }} Dispatched
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Active
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        {{-- VIEW 3: All Notification Logs --}}
        @else
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-6 border-b border-border flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-navy">Live Platform Notification Feed</h3>
                        <p class="text-xs text-text-muted mt-0.5">Real-time alert dispatch log across all registered portal accounts.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-text-muted uppercase text-[10px] font-bold border-b border-border">
                            <tr>
                                <th class="py-3 px-6">Recipient</th>
                                <th class="py-3 px-6">Type & Sound</th>
                                <th class="py-3 px-6">Title & Summary</th>
                                <th class="py-3 px-6">Status</th>
                                <th class="py-3 px-6">Dispatched</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($notifications as $notification)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-4 px-6 font-bold text-navy">{{ $notification->user->name ?? 'User' }}</td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-accent border border-blue-200">
                                            {{ str($notification->type)->headline() }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-navy">{{ $notification->title }}</div>
                                        <div class="text-text-muted text-[11px] truncate max-w-sm mt-0.5">{{ $notification->body }}</div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $notification->read_at ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700' }}">
                                            {{ $notification->read_at ? 'Read' : 'Delivered' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-text-muted font-medium">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-12 text-text-muted">
                                        No notification history records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($notifications, 'links'))
                    <div class="p-4 border-t border-border">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-admin-layout>
