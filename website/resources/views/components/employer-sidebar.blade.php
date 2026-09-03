@props(['title' => 'Employer Portal'])
<?php $branding = app(\App\Services\SiteSettingsService::class)->branding(); ?>
<?php $employerCompany = auth()->user()?->companies()->first(); ?>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — Employer Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface antialiased" x-data="{ sidebarOpen: true, mobileSidebarOpen: false }">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside
            :class="sidebarOpen ? 'w-64' : 'w-20'"
            class="hidden lg:flex flex-col fixed inset-y-0 left-0 z-30 bg-white border-r border-border transition-all duration-300 overflow-hidden"
        >
            {{-- Logo --}}
            <div class="flex items-center h-20 px-5 border-b border-border flex-shrink-0 bg-white">
                <a href="{{ route('employer.dashboard') }}" class="flex items-center gap-2.5">
                    <img 
                        src="{{ asset($branding['logo_url']) }}"
                        alt="Luckyboss Employment Agency Pte. Ltd" 
                        class="h-12 sm:h-13 w-auto max-h-14 object-contain"
                    >
                </a>
            </div>

            {{-- Company Profile / Subscription Status --}}
            <div class="p-4 border-b border-border">
                <div class="flex items-center gap-3">
                    @if($employerCompany?->logo_path)
                        <img src="{{ asset($employerCompany->logo_path) }}" alt="{{ $employerCompany->name }} logo" class="w-10 h-10 rounded-full object-contain border border-slate-200 bg-white p-1">
                    @else
                        <x-ui.avatar :name="auth()->user()->name ?? 'Company'" size="md" />
                    @endif
                    <div x-show="sidebarOpen" x-transition class="min-w-0">
                        <p class="text-sm font-semibold text-text-primary truncate">{{ auth()->user()->name ?? 'Company' }}</p>
                        <x-ui.badge variant="success" dot class="mt-1">Pro Plan</x-ui.badge>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <?php
                    $nav = [
                        ['label' => 'Dashboard', 'route' => 'employer.dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['label' => 'My Jobs', 'route' => 'employer.jobs.index', 'icon' => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0'],
                        ['label' => 'Post New Job', 'route' => 'employer.jobs.create', 'icon' => 'M12 4.5v15m7.5-7.5h-15'],
                        ['label' => 'Candidates', 'route' => 'employer.portal', 'param' => 'candidates', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
                        ['label' => 'Interviews', 'route' => 'employer.portal', 'param' => 'interviews', 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
                        ['label' => 'Offers', 'route' => 'employer.portal', 'param' => 'offers', 'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25'],
                        ['label' => 'Billing', 'route' => 'employer.portal', 'param' => 'billing', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z'],
                        ['label' => 'Team', 'route' => 'employer.portal', 'param' => 'team', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z'],
                        ['label' => 'Company Profile', 'route' => 'employer.portal', 'param' => 'company-profile', 'icon' => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z'],
                        ['label' => 'AI Tools', 'route' => 'employer.portal', 'param' => 'ai-tools', 'icon' => 'M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5'],
                        ['label' => 'Analytics', 'route' => 'employer.portal', 'param' => 'analytics', 'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
                    ];
                ?>

                @foreach($nav as $item)
                    <?php
                        $isActive = isset($item['param']) 
                            ? request()->routeIs($item['route']) && request()->route('section') === $item['param']
                            : request()->routeIs($item['route'] . '*');
                        
                        $routeUrl = isset($item['param']) ? route($item['route'], $item['param']) : route($item['route']);
                    ?>
                    <a href="{{ $routeUrl }}"
                       @class([
                           'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200',
                           'bg-secondary-50 text-secondary-700' => $isActive,
                           'text-text-secondary hover:text-text-primary hover:bg-surface-sunken' => !$isActive,
                       ])
                    >
                        <svg class="w-5 h-5 flex-shrink-0 {{ $isActive ? 'text-secondary-500' : '' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            {{-- Sidebar Footer --}}
            <div class="border-t border-border p-3">
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 w-full px-3 py-2 rounded-xl text-sm text-text-secondary hover:text-danger hover:bg-red-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                        <span x-show="sidebarOpen" x-transition>Sign Out</span>
                    </button>
                </form>
            </div>

            {{-- Collapse Toggle --}}
            <button @click="sidebarOpen = !sidebarOpen" class="absolute top-20 -right-3 w-6 h-6 rounded-full bg-white border border-border shadow-sm flex items-center justify-center hover:bg-surface-sunken transition-colors">
                <svg :class="sidebarOpen ? '' : 'rotate-180'" class="w-3 h-3 text-text-secondary transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </button>
        </aside>

        {{-- Main Content (0ms static margin layout) --}}
        <div class="flex-1 lg:ml-64 flex flex-col min-w-0">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-border h-16 flex items-center px-6 justify-between">
                <div class="flex items-center gap-3">
                    <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="lg:hidden p-2 -ml-2 mr-3 rounded-lg hover:bg-surface-sunken">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>

                    <h1 class="text-lg font-heading font-bold text-navy leading-tight">{{ $title }}</h1>
                </div>

                <div class="flex items-center gap-4">
                    {{-- Real-time Notification Bell --}}
                    <div class="relative" x-data="notificationCenter()">
                        <button @click="toggle()" @mouseenter="playChime('applicant_alert')" type="button" class="relative p-2 rounded-xl text-slate-600 hover:text-navy hover:bg-slate-100 transition-colors cursor-pointer" title="ATS Notifications">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span x-show="unreadCount > 0" class="absolute top-1 right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 text-[10px] font-bold text-white items-center justify-center" x-text="unreadCount"></span>
                            </span>
                        </button>

                        {{-- Dropdown --}}
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-border z-50 overflow-hidden">
                            <div class="p-4 bg-navy text-white flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold">Employer Notifications</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white" x-text="unreadCount + ' new'"></span>
                                </div>
                                <button @click="markAllAsRead()" class="text-xs text-secondary-300 hover:text-white transition-colors cursor-pointer">
                                    Mark all read
                                </button>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-border">
                                <template x-for="n in notifications" :key="n.id">
                                    <div @click="playChime(n.type)" class="p-4 hover:bg-slate-50 transition-colors cursor-pointer flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" :class="n.unread ? 'bg-secondary-100 text-secondary-700' : 'bg-slate-100 text-slate-500'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-navy" x-text="n.title"></p>
                                            <p class="text-xs text-text-secondary mt-0.5 line-clamp-2" x-text="n.body"></p>
                                            <span class="text-[10px] text-text-muted mt-1 block" x-text="n.time"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="p-3 bg-slate-50 border-t border-border text-center">
                                <a href="{{ route('employer.portal', 'notifications') }}" class="text-xs font-bold text-accent hover:underline">
                                    View Notification Settings →
                                </a>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary btn-sm hidden sm:inline-flex">
                        Post New Job
                    </a>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="p-6">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-6"><x-ui.alert type="success" dismissible>{{ session('success') }}</x-ui.alert></div>
                @endif
                @if(session('error'))
                    <div class="mb-6"><x-ui.alert type="danger" dismissible>{{ session('error') }}</x-ui.alert></div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
