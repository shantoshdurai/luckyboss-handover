<x-admin-layout title="Admin Dashboard">
    {{-- Metric Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @php
            $metricIcons = [
                'companies' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>',
                'candidates' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>',
                'jobs' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>',
                'features' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>',
            ];
            $metricColors = [
                'companies' => 'bg-blue-50 text-accent',
                'candidates' => 'bg-emerald-50 text-emerald-600',
                'jobs' => 'bg-purple-50 text-purple-600',
                'features' => 'bg-amber-50 text-amber-600',
            ];
        @endphp

        @foreach($metrics as $label => $value)
            <div class="bg-white rounded-2xl p-6 border border-border shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-text-muted">{{ str($label)->headline() }}</span>
                    <div class="w-10 h-10 rounded-xl {{ $metricColors[$label] ?? 'bg-surface-sunken text-text-primary' }} flex items-center justify-center">
                        {!! $metricIcons[$label] ?? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>' !!}
                    </div>
                </div>
                <div class="text-3xl font-heading font-bold text-navy mb-1">{{ number_format($value) }}</div>
                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Live Platform Data
                </span>
            </div>
        @endforeach
    </div>

    {{-- Main Sections Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Quick Management Cards --}}
        <div class="lg:col-span-7 bg-white rounded-2xl p-6 border border-border shadow-sm">
            <h2 class="text-xl font-heading font-bold text-navy mb-1">Quick Management</h2>
            <p class="text-sm text-text-secondary mb-6">Access platform configuration masters and core records.</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('admin.masters.index', 'company-types') }}" class="p-5 rounded-xl bg-surface-sunken border border-border hover:border-accent hover:shadow-md transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-accent flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="font-bold text-sm text-navy group-hover:text-accent transition-colors">Company Types</h3>
                    <p class="text-xs text-text-muted mt-1">Manage employer classifications</p>
                </a>

                <a href="{{ route('admin.masters.index', 'job-categories') }}" class="p-5 rounded-xl bg-surface-sunken border border-border hover:border-accent hover:shadow-md transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    </div>
                    <h3 class="font-bold text-sm text-navy group-hover:text-accent transition-colors">Job Categories</h3>
                    <p class="text-xs text-text-muted mt-1">Control website expertise cards</p>
                </a>

                <a href="{{ route('admin.blogs.index') }}" class="p-5 rounded-xl bg-surface-sunken border border-border hover:border-accent hover:shadow-md transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                    </div>
                    <h3 class="font-bold text-sm text-navy group-hover:text-accent transition-colors">Recruitment Blog</h3>
                    <p class="text-xs text-text-muted mt-1">Publish public career content</p>
                </a>
            </div>
        </div>

        {{-- Feature Flags Control --}}
        <div class="lg:col-span-5 bg-white rounded-2xl p-6 border border-border shadow-sm">
            <h2 class="text-xl font-heading font-bold text-navy mb-1">Feature Controls</h2>
            <p class="text-sm text-text-secondary mb-4">Module availability status enforced platform-wide.</p>

            <div class="divide-y divide-border">
                @foreach($features as $feature)
                    <div class="py-3 flex items-center justify-between gap-3">
                        <div>
                            <span class="text-sm font-bold text-navy block">{{ $feature->name }}</span>
                            <span class="text-[11px] text-text-muted">{{ $feature->description ?: 'Platform-wide module enforcement' }}</span>
                        </div>
                        <form method="POST" action="{{ route('admin.ai-api.flags.update', $feature) }}" class="inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_enabled" value="{{ $feature->is_enabled ? 0 : 1 }}">
                            <button type="submit" 
                                    title="Click to {{ $feature->is_enabled ? 'Disable' : 'Enable' }} {{ $feature->name }}"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $feature->is_enabled ? 'bg-emerald-500' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $feature->is_enabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-admin-layout>