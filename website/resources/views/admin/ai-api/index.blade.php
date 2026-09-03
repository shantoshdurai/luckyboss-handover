<x-admin-layout :title="str($view)->headline() . ' | Luckyboss Admin'" :heading="str($view)->headline()">
    <div class="space-y-6">
        {{-- Header & Sub-Navigation --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-xs">
            <div>
                <h2 class="text-2xl font-heading font-extrabold text-navy">AI & API Control Center</h2>
                <p class="text-xs text-text-secondary mt-1">
                    Manage AI providers, encrypted secrets, live feature flags, usage limits, and error observability.
                </p>
            </div>

            <div class="flex items-center gap-2 overflow-x-auto">
                <a href="{{ route('admin.ai-api.index', ['view' => 'ai-dashboard']) }}"
                   @class([
                       'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0',
                       'bg-accent text-white shadow-sm' => $view === 'ai-dashboard' || !$view,
                       'bg-slate-100 text-text-secondary hover:bg-slate-200' => $view !== 'ai-dashboard' && $view,
                   ])>
                    API Integrations
                </a>
                <a href="{{ route('admin.ai-api.index', ['view' => 'global-ai-settings']) }}"
                   @class([
                       'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0',
                       'bg-accent text-white shadow-sm' => $view === 'global-ai-settings',
                       'bg-slate-100 text-text-secondary hover:bg-slate-200' => $view !== 'global-ai-settings',
                   ])>
                    Feature Flags & Toggles
                </a>
                <a href="{{ route('admin.ai-api.index', ['view' => 'api-errors']) }}"
                   @class([
                       'px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0',
                       'bg-accent text-white shadow-sm' => $view === 'api-errors',
                       'bg-slate-100 text-text-secondary hover:bg-slate-200' => $view !== 'api-errors',
                   ])>
                    Error Logs ({{ $errors->count() }})
                </a>
            </div>
        </div>

        {{-- VIEW 1: Feature Flags & Toggles --}}
        @if($view === 'global-ai-settings')
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-6 border-b border-border">
                    <h3 class="text-lg font-bold text-navy">Global Feature Controls</h3>
                    <p class="text-xs text-text-muted mt-0.5">Toggle live modules and capabilities platform-wide.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-text-muted uppercase text-[10px] font-bold border-b border-border">
                            <tr>
                                <th class="py-3.5 px-6">Feature Module</th>
                                <th class="py-3.5 px-6">System Scope & Description</th>
                                <th class="py-3.5 px-6">Live Status</th>
                                <th class="py-3.5 px-6 text-right">Switch Toggle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($flags as $flag)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4 px-6 font-bold text-navy text-sm">{{ $flag->name }}</td>
                                    <td class="py-4 px-6 text-text-secondary font-medium">{{ $flag->description ?: 'Enforced across recruitment ecosystem.' }}</td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $flag->is_enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                            {{ $flag->is_enabled ? 'Active / Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <form method="POST" action="{{ route('admin.ai-api.flags.update', $flag) }}" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_enabled" value="{{ $flag->is_enabled ? 0 : 1 }}">
                                            <button type="submit" 
                                                    title="Click to {{ $flag->is_enabled ? 'Disable' : 'Enable' }} {{ $flag->name }}"
                                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $flag->is_enabled ? 'bg-emerald-500' : 'bg-slate-200' }}">
                                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $flag->is_enabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-12 text-text-muted">No feature flags registered.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        {{-- VIEW 2: API Error Logs --}}
        @elseif($view === 'api-errors')
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-6 border-b border-border">
                    <h3 class="text-lg font-bold text-navy">API Diagnostics & Error History</h3>
                    <p class="text-xs text-text-muted mt-0.5">Recorded third-party exceptions and timeouts.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-text-muted uppercase text-[10px] font-bold border-b border-border">
                            <tr>
                                <th class="py-3.5 px-6">Integration</th>
                                <th class="py-3.5 px-6">Provider</th>
                                <th class="py-3.5 px-6">Error Detail</th>
                                <th class="py-3.5 px-6">Timestamp</th>
                                <th class="py-3.5 px-6 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($errors as $integration)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-4 px-6 font-bold text-navy">{{ $integration->name }}</td>
                                    <td class="py-4 px-6 font-medium text-text-secondary">{{ $integration->provider ?: 'Default' }}</td>
                                    <td class="py-4 px-6 font-mono text-[11px] text-rose-600 max-w-md truncate">{{ $integration->last_error }}</td>
                                    <td class="py-4 px-6 text-text-muted">{{ $integration->updated_at->diffForHumans() }}</td>
                                    <td class="py-4 px-6 text-right">
                                        <form method="POST" action="{{ route('admin.ai-api.integrations.error.clear', $integration) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline btn-sm py-1 px-3 text-xs text-rose-600 hover:bg-rose-50 border-rose-200 cursor-pointer">
                                                Clear Error
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-12 text-emerald-600 font-semibold">
                                        ✓ No API errors detected. All systems healthy.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        {{-- VIEW 3: API Integrations & Providers --}}
        @else
            <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
                <div class="p-6 border-b border-border">
                    <h3 class="text-lg font-bold text-navy">Connected Services & API Gateways</h3>
                    <p class="text-xs text-text-muted mt-0.5">Encrypted API keys and active rate limits.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-text-muted uppercase text-[10px] font-bold border-b border-border">
                            <tr>
                                <th class="py-3.5 px-6">Service Name</th>
                                <th class="py-3.5 px-6">Provider</th>
                                <th class="py-3.5 px-6">Environment</th>
                                <th class="py-3.5 px-6">Usage Count</th>
                                <th class="py-3.5 px-6">Monthly Quota</th>
                                <th class="py-3.5 px-6">Status</th>
                                <th class="py-3.5 px-6 text-right">Toggle Active</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($integrations as $integration)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4 px-6 font-bold text-navy">{{ $integration->name }}</td>
                                    <td class="py-4 px-6 text-text-secondary font-medium">{{ $integration->provider ?: 'Internal' }}</td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                            {{ $integration->environment }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 font-mono font-bold text-navy">{{ number_format($integration->usage_count) }}</td>
                                    <td class="py-4 px-6 text-text-muted">{{ $integration->monthly_limit ? number_format($integration->monthly_limit) : 'Unlimited' }}</td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $integration->is_enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                            {{ $integration->is_enabled ? 'Connected' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <form method="POST" action="{{ route('admin.ai-api.integrations.update', $integration) }}" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_enabled" value="{{ $integration->is_enabled ? 0 : 1 }}">
                                            <button type="submit" 
                                                    title="Click to {{ $integration->is_enabled ? 'Disable' : 'Enable' }} {{ $integration->name }}"
                                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $integration->is_enabled ? 'bg-emerald-500' : 'bg-slate-200' }}">
                                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $integration->is_enabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-text-muted">No API integrations registered.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
