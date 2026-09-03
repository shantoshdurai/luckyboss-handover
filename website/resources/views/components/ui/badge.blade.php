@props([
    'variant' => 'neutral',
    'dot' => false,
])

@php
    $variants = [
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger'  => 'badge-danger',
        'info'    => 'badge-info',
        'neutral' => 'badge-neutral',
        'primary' => 'badge-primary',
    ];
    $class = 'badge ' . ($variants[$variant] ?? $variants['neutral']);
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>
    @if($dot)
        <span @class([
            'w-1.5 h-1.5 rounded-full',
            'bg-green-500' => $variant === 'success',
            'bg-amber-500' => $variant === 'warning',
            'bg-red-500'   => $variant === 'danger',
            'bg-blue-500'  => $variant === 'info',
            'bg-slate-400' => $variant === 'neutral',
            'bg-navy'      => $variant === 'primary',
        ])></span>
    @endif
    {{ $slot }}
</span>
