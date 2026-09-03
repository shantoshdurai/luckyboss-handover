@props([
    'type' => 'spinner',
    'size' => 'md',
])

@if($type === 'spinner')
    @php
        $sizes = ['sm' => 'w-4 h-4', 'md' => 'w-6 h-6', 'lg' => 'w-10 h-10'];
    @endphp
    <svg {{ $attributes->merge(['class' => 'animate-spin text-accent ' . ($sizes[$size] ?? $sizes['md'])]) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
@elseif($type === 'skeleton')
    <div {{ $attributes->merge(['class' => 'skeleton h-4 rounded']) }}></div>
@elseif($type === 'card-skeleton')
    <div class="card p-6 space-y-4">
        <div class="skeleton h-4 w-3/4 rounded"></div>
        <div class="skeleton h-4 w-1/2 rounded"></div>
        <div class="skeleton h-20 w-full rounded"></div>
        <div class="flex gap-3">
            <div class="skeleton h-8 w-20 rounded-lg"></div>
            <div class="skeleton h-8 w-20 rounded-lg"></div>
        </div>
    </div>
@endif
