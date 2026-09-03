@props(['items' => []])

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'flex items-center gap-2 text-sm text-text-muted']) }}>
    @foreach($items as $item)
        @if(!$loop->first)
            <svg class="w-4 h-4 text-border-strong flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
        @endif

        @if(isset($item['url']) && !$loop->last)
            <a href="{{ $item['url'] }}" class="hover:text-accent transition-colors">{{ $item['label'] }}</a>
        @else
            <span class="font-medium text-text-primary truncate">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav
