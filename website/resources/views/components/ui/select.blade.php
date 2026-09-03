@props([
    'label' => null,
    'name' => null,
    'error' => null,
    'help' => null,
    'required' => false,
    'placeholder' => 'Select an option',
    'options' => [],
])

@php
    $name = $name ?? $attributes->get('name');
    $error = $error ?? ($name ? $errors->first($name) : null);
    $inputClass = 'form-input' . ($error ? ' error' : '');
@endphp

<div class="space-y-1">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="form-label">
            {{ $label }}
            @if($required) <span class="text-danger ml-0.5">*</span> @endif
        </label>
    @endif

    <select
        @if($name) name="{{ $name }}" id="{{ $name }}" @endif
        @if($required) required @endif
        {{ $attributes->merge(['class' => $inputClass]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @if(is_array($options) && count($options) > 0)
            @foreach($options as $value => $text)
                <option value="{{ $value }}" @selected(old($name) == $value)>{{ $text }}</option>
            @endforeach
        @endif
        {{ $slot }}
    </select>

    @if($error)
        <p class="form-error" role="alert">{{ $error }}</p>
    @elseif($help)
        <p class="form-help">{{ $help }}</p>
    @endif
</div>
