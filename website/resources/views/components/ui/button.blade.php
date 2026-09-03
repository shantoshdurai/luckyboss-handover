@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'disabled' => false,
    'loading' => false,
    'icon' => null,
])

@php
    $tag = $href ? 'a' : 'button';

    $variants = [
        'primary'   => 'btn btn-primary',
        'secondary' => 'btn btn-secondary',
        'accent'    => 'btn btn-accent',
        'outline'   => 'btn btn-outline',
        'ghost'     => 'btn btn-ghost',
        'danger'    => 'btn btn-danger',
    ];

    $sizes = [
        'sm' => 'btn-sm',
        'md' => '',
        'lg' => 'btn-lg',
        'xl' => 'btn-xl',
    ];

    $classes = ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? '');
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    @if($tag === 'button') type="{{ $type }}" @endif
    @if($disabled) disabled @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if($loading) x-data="{ loading: true }" @endif
>
    @if($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    @endif
    {{ $slot }}
</{{ $tag }}>
