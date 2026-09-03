@props([
    'tabs' => [],
    'active' => null,
])

@php
    $active = $active ?? (count($tabs) > 0 ? array_key_first($tabs) : '');
@endphp

<div x-data="tabs('{{ $active }}')" {{ $attributes }}>
    {{-- Tab Navigation --}}
    <div class="flex border-b border-border overflow-x-auto -mb-px" role="tablist">
        @foreach($tabs as $key => $label)
            <button
                @click="switchTab('{{ $key }}')"
                :class="isActive('{{ $key }}') ? 'border-accent text-accent' : 'border-transparent text-text-muted hover:text-text-secondary hover:border-border-strong'"
                class="px-4 py-3 text-sm font-medium border-b-2 whitespace-nowrap transition-colors focus:outline-none"
                role="tab"
                :aria-selected="isActive('{{ $key }}')"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Tab Content --}}
    <div class="mt-4">
        {{ $slot }}
    </div>
</div>
