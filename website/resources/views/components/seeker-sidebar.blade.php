@props(['title' => 'Job Seeker Portal'])
<?php $branding = app(\App\Services\SiteSettingsService::class)->branding(); ?>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | Luckyboss Employment Agency Candidate Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased font-sans text-text-primary" x-data="{ sidebarOpen: true, mobileSidebarOpen: false }">
    <div class="flex min-h-screen">
        {{-- Desktop Candidate Sidebar (Clean White with High-Contrast Typography) --}}
        <aside
            :class="sidebarOpen ? 'w-64' : 'w-20'"
            class="hidden lg:flex flex-col fixed inset-y-0 left-0 z-30 bg-white border-r border-border overflow-hidden shadow-xs transition-all duration-200"
        >
            {{-- Brand Logo (Crystal Clear on White Background) --}}
            <div class="flex items-center justify-between h-20 px-5 border-b border-border shrink-0 bg-white">
                <a href="{{ route('seeker.dashboard') }}" class="flex items-center gap-2.5">
                    <img 
                        src="{{ asset($branding['logo_url']) }}"
                        alt="Luckyboss" 
                        class="h-12 sm:h-13 w-auto max-h-14 object-contain"
                    >
                </a>
            </div>

            {{-- Verified Candidate Card --}}
            <div x-show="sidebarOpen" class="p-4 border-b border-border bg-slate-50/70">
                <div class="flex items-center gap-3">
                        @if(auth()->user()->candidateProfile?->profile_photo_path)
                            <img src="{{ asset(auth()->user()->candidateProfile->profile_photo_path) }}" alt="{{ auth()->user()->name }} profile picture" class="w-10 h-10 rounded-xl object-cover border border-emerald-200 shrink-0" loading="lazy">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-secondary-100 text-secondary-700 flex items-center justify-center font-bold shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                        @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <p class="text-sm font-bold text-navy truncate">{{ auth()->user()->name ?? 'Candidate' }}</p>
                            <span class="inline-flex text-[9px] font-bold px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">Verified</span>
                        </div>
                        <a href="{{ route('seeker.profile.edit') }}" class="text-xs text-secondary-600 hover:text-navy font-semibold transition-colors">Edit Profile &rarr;</a>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 scrollbar-thin">
                <?php
                    $currentTab = request('tab', 'dashboard');
                    $nav = [
                        ['label' => 'Dashboard', 'url' => route('seeker.dashboard'), 'active' => request()->routeIs('seeker.dashboard') && empty(request('tab')), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['label' => 'My Applications', 'url' => route('seeker.dashboard', ['tab' => 'applications']), 'active' => request('tab') === 'applications', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['label' => 'Matching Jobs', 'url' => route('seeker.dashboard', ['tab' => 'matching']), 'active' => request('tab') === 'matching', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                        ['label' => 'Saved Jobs', 'url' => route('seeker.dashboard', ['tab' => 'saved']), 'active' => request('tab') === 'saved', 'icon' => 'M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z'],
                        ['label' => 'Candidate Profile', 'url' => route('seeker.profile.edit'), 'active' => request()->routeIs('seeker.profile.*'), 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                        ['label' => 'Find Jobs', 'url' => route('jobs.index'), 'active' => false, 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                    ];
                ?>

                @foreach($nav as $item)
                    <a href="{{ $item['url'] }}"
                       @class([
                           'flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150',
                           'bg-navy text-white shadow-xs font-bold' => $item['active'],
                           'text-slate-700 hover:text-navy hover:bg-slate-100' => !$item['active'],
                       ])
                    >
                        <svg class="w-4 h-4 shrink-0 {{ $item['active'] ? 'text-white' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                        </svg>
                        <span x-show="sidebarOpen" class="whitespace-nowrap">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            {{-- Sidebar Footer --}}
            <div class="p-3 border-t border-border shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 w-full px-3 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        <span x-show="sidebarOpen">Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Content Container (Static lg:ml-64 for 0ms instantaneous snappy navigation) --}}
        <div class="flex-1 lg:ml-64 flex flex-col min-w-0">
            {{-- Top Bar --}}
            <header class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-border h-16 flex items-center justify-between px-6 shadow-xs">
                <div class="flex items-center gap-3">
                    <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="lg:hidden p-2 rounded-xl border border-border text-navy hover:bg-slate-50 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h1 class="text-lg font-heading font-extrabold text-navy leading-tight">{{ $title }}</h1>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Live Database Notification Bell with Audio Chimes --}}
                    <div class="relative" x-data="notificationCenter()">
                        <button @click="toggle()" @mouseenter="playChime('job_match')" type="button" class="relative p-2 rounded-xl text-slate-600 hover:text-navy hover:bg-slate-100 transition-colors cursor-pointer" title="Candidate Notifications">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span x-show="unreadCount > 0" class="absolute top-1 right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 text-[10px] font-bold text-white items-center justify-center" x-text="unreadCount"></span>
                            </span>
                        </button>

                        {{-- Dropdown Panel --}}
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-border z-50 overflow-hidden">
                            <div class="p-4 bg-navy text-white flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold">Candidate Notifications</h4>
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
                        </div>
                    </div>

                    <a href="{{ route('jobs.index') }}" class="btn btn-primary btn-sm py-1.5 px-3.5 text-xs font-bold shadow-xs">
                        Browse Jobs
                    </a>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 p-6 sm:p-8 max-w-7xl w-full mx-auto space-y-6">
                @if(session('success'))
                    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2 shadow-xs">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2 shadow-xs">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
