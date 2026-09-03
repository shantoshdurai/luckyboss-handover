@props([
    'value' => '0',
    'label' => '',
    'trend' => null,
    'trendUp' => true,
    'color' => 'blue',
])

@php
    $bgColors = [
        'blue'   => 'bg-blue-50 text-blue-600',
        'green'  => 'bg-green-50 text-green-600',
        'amber'  => 'bg-amber-50 text-amber-600',
        'red'    => 'bg-red-50 text-red-600',
        'purple' => 'bg-purple-50 text-purple-600',
        'navy'   => 'bg-primary-50 text-primary-800',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'stat-card']) }}>
    <div class="stat-card-icon {{ $bgColors[$color] ?? $bgColors['blue'] }}">
        {{ $slot }}
    </div>
    <div>
        <div class="stat-card-value">{{ $value }}</div>
        <div class="stat-card-label">{{ $label }}</div>
        @if($trend)
            <div @class([
                'text-xs font-semibold mt-1.5 flex items-center gap-1',
                'text-green-600' => $trendUp,
                'text-red-500' => !$trendUp,
            ])>
                @if($trendUp) ↑ @else ↓ @endif
                {{ $trend }}
            </div>
        @endif
    </div>
</div>
