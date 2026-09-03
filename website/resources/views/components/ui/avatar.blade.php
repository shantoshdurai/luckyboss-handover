@props([
    'src' => null,
    'name' => '',
    'size' => 'md',
])

@php
    $sizes = [
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-12 h-12 text-base',
        'xl' => 'w-16 h-16 text-lg',
    ];
    $initials = collect(explode(' ', $name))->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
@endphp

<div {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center rounded-full overflow-hidden bg-primary-100 text-primary-700 font-semibold flex-shrink-0 ' . ($sizes[$size] ?? $sizes['md'])]) }}>
    @if($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="w-full h-full object-cover" loading="lazy" />
    @else
        <span>{{ $initials ?: '?' }}</span>
    @endif
</div>
