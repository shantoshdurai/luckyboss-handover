@props([
    'value' => 0,
    'max' => 100,
    'label' => null,
    'showPercent' => true,
    'size' => 'md',
    'color' => 'accent',
])

@php
    $percent = $max > 0 ? min(100, round(($value / $max) * 100)) : 0;
    $heights = ['sm' => 'h-1.5', 'md' => 'h-2.5', 'lg' => 'h-4'];
    $colors = [
        'accent' => 'bg-accent',
        'green' => 'bg-secondary-500',
        'navy' => 'bg-navy',
        'danger' => 'bg-danger',
    ];
@endphp

<div {{ $attributes }}>
    @if($label || $showPercent)
        <div class="flex items-center justify-between mb-1.5">
            @if($label) <span class="text-sm font-medium text-text-primary">{{ $label }}</span> @endif
            @if($showPercent) <span class="text-sm font-semibold text-text-secondary">{{ $percent }}%</span> @endif
        </div>
    @endif
    <div class="w-full bg-surface-sunken rounded-full {{ $heights[$size] ?? $heights['md'] }} overflow-hidden">
        <div
            class="{{ $colors[$color] ?? $colors['accent'] }} {{ $heights[$size] ?? $heights['md'] }} rounded-full transition-all duration-500 ease-out"
            style="width: {{ $percent }}%"
            role="progressbar"
            aria-valuenow="{{ $value }}"
            aria-valuemin="0"
            aria-valuemax="{{ $max }}"
        ></div>
    </div>
</div>
