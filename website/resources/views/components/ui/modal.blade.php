@props([
    'maxWidth' => 'lg',
    'title' => null,
])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ];
@endphp

<div
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    aria-modal="true"
    role="dialog"
>
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>

    {{-- Dialog --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            @keydown.escape.window="close()"
            class="relative w-full {{ $widths[$maxWidth] ?? $widths['lg'] }} bg-white rounded-2xl shadow-modal"
        >
            @if($title)
                <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold font-heading text-navy">{{ $title }}</h3>
                    <button @click="close()" class="p-1.5 rounded-lg hover:bg-surface-sunken transition-colors" aria-label="Close">
                        <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            @endif

            <div class="p-6">
                {{ $slot }}
            </div>

            @if(isset($footer))
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-border bg-surface rounded-b-2xl">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
