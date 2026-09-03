@props([
    'label' => null,
    'name' => null,
    'error' => null,
    'help' => null,
    'required' => false,
    'rows' => 4,
])

@php
    $name = $name ?? $attributes->get('name');
    $error = $error ?? ($name ? $errors->first($name) : null);
@endphp

<div class="space-y-1">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="form-label">
            {{ $label }}
            @if($required) <span class="text-danger ml-0.5">*</span> @endif
        </label>
    @endif

    <textarea
        @if($name) name="{{ $name }}" id="{{ $name }}" @endif
        rows="{{ $rows }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'form-input resize-y' . ($error ? ' error' : '')]) }}
    >{{ $slot }}</textarea>

    @if($error)
        <p class="form-error" role="alert">{{ $error }}</p>
    @elseif($help)
        <p class="form-help">{{ $help }}</p>
    @endif
</div>
