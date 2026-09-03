@props([
    'padding' => true,
    'hover' => false,
])

<div {{ $attributes->merge(['class' => 'card' . ($hover ? ' hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200' : '')]) }}>
    @if(isset($header))
        <div class="card-header">
            {{ $header }}
        </div>
    @endif

    <div @class(['card-body' => $padding, 'p-0' => !$padding])>
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
