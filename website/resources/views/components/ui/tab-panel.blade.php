@props(['name'])

<div x-show="isActive('{{ $name }}')" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" role="tabpanel" {{ $attributes }}>
    {{ $slot }}
</div>
