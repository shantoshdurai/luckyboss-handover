# Task 02: Blade Component Library

## Context
You are working on **Lucky Boss Portal** at `c:\Luckyboss\luckyboss-app`. This is a Laravel 12 + Tailwind CSS 4 + Alpine.js project. The design system (Task 01) has already set up brand colors, fonts (Inter for body, Plus Jakarta Sans for headings), and base CSS classes in `resources/css/app.css`.

**Brand Colors:**
- Navy (primary): `#031f49` → Tailwind: `primary-900`
- Green (secondary): `#18a66a` → Tailwind: `secondary-500`
- Accent blue: `#2563eb` → Tailwind: `primary-500` / `accent`

All components go in: `c:\Luckyboss\luckyboss-app\resources\views\components\ui\`

Create this directory first if it doesn't exist.

## CRITICAL RULES
1. Use ONLY Tailwind CSS utility classes — NO inline styles, NO `<style>` blocks
2. Use Alpine.js (`x-data`, `x-show`, `x-on`, `x-bind`, `x-transition`) for interactivity
3. Every component must be accessible (ARIA attributes, keyboard navigation, focus management)
4. Every component must be responsive (mobile-first)
5. Use Laravel Blade component syntax with `@props`, `{{ $attributes }}`, `{{ $slot }}`

---

## Component 1: Button
**File**: `resources/views/components/ui/button.blade.php`

```blade
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
```

**Usage examples:**
```blade
<x-ui.button>Save Changes</x-ui.button>
<x-ui.button variant="secondary" size="lg">Apply Now</x-ui.button>
<x-ui.button variant="outline" href="/jobs">Browse Jobs</x-ui.button>
<x-ui.button variant="danger" type="submit">Delete</x-ui.button>
```

---

## Component 2: Card
**File**: `resources/views/components/ui/card.blade.php`

```blade
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
```

**Usage:**
```blade
<x-ui.card>
    <x-slot:header>Card Title</x-slot:header>
    <p>Content here</p>
    <x-slot:footer>
        <x-ui.button size="sm">Action</x-ui.button>
    </x-slot:footer>
</x-ui.card>
```

---

## Component 3: Badge
**File**: `resources/views/components/ui/badge.blade.php`

```blade
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
```

**Usage:**
```blade
<x-ui.badge variant="success" dot>Active</x-ui.badge>
<x-ui.badge variant="danger">Expired</x-ui.badge>
<x-ui.badge variant="warning">Pending Review</x-ui.badge>
```

---

## Component 4: Input
**File**: `resources/views/components/ui/input.blade.php`

```blade
@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'error' => null,
    'help' => null,
    'required' => false,
    'icon' => null,
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
            @if($required)
                <span class="text-danger ml-0.5">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-text-muted">{!! $icon !!}</span>
            </div>
        @endif

        <input
            type="{{ $type }}"
            @if($name) name="{{ $name }}" id="{{ $name }}" @endif
            @if($required) required @endif
            {{ $attributes->merge(['class' => $inputClass . ($icon ? ' pl-10' : '')]) }}
            @if($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        >
    </div>

    @if($error)
        <p id="{{ $name }}-error" class="form-error" role="alert">{{ $error }}</p>
    @elseif($help)
        <p class="form-help">{{ $help }}</p>
    @endif
</div>
```

**Usage:**
```blade
<x-ui.input label="Email Address" name="email" type="email" required placeholder="you@example.com" />
<x-ui.input label="Password" name="password" type="password" required help="Must be at least 8 characters" />
```

---

## Component 5: Select
**File**: `resources/views/components/ui/select.blade.php`

```blade
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
```

---

## Component 6: Textarea
**File**: `resources/views/components/ui/textarea.blade.php`

```blade
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
```

---

## Component 7: Alert / Toast
**File**: `resources/views/components/ui/alert.blade.php`

```blade
@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $styles = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
        'danger'  => 'bg-red-50 border-red-200 text-red-800',
        'info'    => 'bg-blue-50 border-blue-200 text-blue-800',
    ];
    $iconColors = [
        'success' => 'text-green-500',
        'warning' => 'text-amber-500',
        'danger'  => 'text-red-500',
        'info'    => 'text-blue-500',
    ];
@endphp

<div
    {{ $attributes->merge(['class' => 'flex items-start gap-3 p-4 rounded-xl border ' . ($styles[$type] ?? $styles['info'])]) }}
    role="alert"
    @if($dismissible) x-data="{ show: true }" x-show="show" x-transition @endif
>
    {{-- Icon --}}
    <div class="flex-shrink-0 mt-0.5 {{ $iconColors[$type] ?? $iconColors['info'] }}">
        @if($type === 'success')
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        @elseif($type === 'danger')
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
        @elseif($type === 'warning')
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        @else
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
        @endif
    </div>

    <div class="flex-1 min-w-0">
        @if($title)
            <h4 class="font-semibold text-sm mb-0.5">{{ $title }}</h4>
        @endif
        <div class="text-sm">{{ $slot }}</div>
    </div>

    @if($dismissible)
        <button @click="show = false" class="flex-shrink-0 ml-auto -mr-1 -mt-1 p-1 rounded-lg hover:bg-black/5 transition-colors" aria-label="Dismiss">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
        </button>
    @endif
</div>
```

---

## Component 8: Empty State
**File**: `resources/views/components/ui/empty-state.blade.php`

```blade
@props([
    'title' => 'Nothing here yet',
    'description' => null,
    'icon' => 'inbox',
    'action' => null,
    'actionUrl' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-16 px-6 text-center']) }}>
    <div class="w-16 h-16 rounded-2xl bg-surface-sunken flex items-center justify-center mb-5">
        <svg class="w-8 h-8 text-text-muted" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            @if($icon === 'inbox')
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859M12 3v8.25m0 0l-3-3m3 3l3-3" />
            @elseif($icon === 'briefcase')
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
            @elseif($icon === 'users')
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
            @elseif($icon === 'search')
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            @elseif($icon === 'document')
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            @else
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859M12 3v8.25m0 0l-3-3m3 3l3-3" />
            @endif
        </svg>
    </div>

    <h3 class="text-lg font-semibold text-text-primary mb-1.5">{{ $title }}</h3>

    @if($description)
        <p class="text-sm text-text-muted max-w-sm mb-6">{{ $description }}</p>
    @endif

    @if($action && $actionUrl)
        <x-ui.button variant="primary" :href="$actionUrl">{{ $action }}</x-ui.button>
    @endif

    {{ $slot }}
</div>
```

---

## Component 9: Stat Card
**File**: `resources/views/components/ui/stat-card.blade.php`

```blade
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
```

---

## Component 10: Tabs
**File**: `resources/views/components/ui/tabs.blade.php`

```blade
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
```

**Tab panel helper:**

**File**: `resources/views/components/ui/tab-panel.blade.php`

```blade
@props(['name'])

<div x-show="isActive('{{ $name }}')" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" role="tabpanel" {{ $attributes }}>
    {{ $slot }}
</div>
```

---

## Component 11: Modal
**File**: `resources/views/components/ui/modal.blade.php`

```blade
@props([
    'maxWidth' => 'lg',
    'title' => null,
])

@php
    $widths = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ];
@endphp

<div
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    aria-modal="true"
    role="dialog"
>
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>

    {{-- Dialog --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            @keydown.escape.window="close()"
            class="relative w-full {{ $widths[$maxWidth] ?? $widths['lg'] }} bg-white rounded-2xl shadow-modal"
        >
            @if($title)
                <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                    <h3 class="text-lg font-semibold font-heading text-navy">{{ $title }}</h3>
                    <button @click="close()" class="p-1.5 rounded-lg hover:bg-surface-sunken transition-colors" aria-label="Close">
                        <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            @endif

            <div class="p-6">
                {{ $slot }}
            </div>

            @if(isset($footer))
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-border bg-surface rounded-b-2xl">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
```

---

## Component 12: Progress Bar
**File**: `resources/views/components/ui/progress-bar.blade.php`

```blade
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
```

---

## Component 13: Breadcrumb
**File**: `resources/views/components/ui/breadcrumb.blade.php`

```blade
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
</nav>
```

**Usage:**
```blade
<x-ui.breadcrumb :items="[
    ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
    ['label' => 'Companies', 'url' => route('admin.companies.index')],
    ['label' => 'ABC Corp'],
]" />
```

---

## Component 14: Avatar
**File**: `resources/views/components/ui/avatar.blade.php`

```blade
@props([
    'src' => null,
    'name' => '',
    'size' => 'md',
])

@php
    $sizes = [
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-12 h-12 text-base',
        'xl' => 'w-16 h-16 text-lg',
    ];
    $initials = collect(explode(' ', $name))->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
@endphp

<div {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-center rounded-full overflow-hidden bg-primary-100 text-primary-700 font-semibold flex-shrink-0 ' . ($sizes[$size] ?? $sizes['md'])]) }}>
    @if($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="w-full h-full object-cover" loading="lazy" />
    @else
        <span>{{ $initials ?: '?' }}</span>
    @endif
</div>
```

---

## Component 15: Loading / Skeleton
**File**: `resources/views/components/ui/loading.blade.php`

```blade
@props([
    'type' => 'spinner',
    'size' => 'md',
])

@if($type === 'spinner')
    @php
        $sizes = ['sm' => 'w-4 h-4', 'md' => 'w-6 h-6', 'lg' => 'w-10 h-10'];
    @endphp
    <svg {{ $attributes->merge(['class' => 'animate-spin text-accent ' . ($sizes[$size] ?? $sizes['md'])]) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
@elseif($type === 'skeleton')
    <div {{ $attributes->merge(['class' => 'skeleton h-4 rounded']) }}></div>
@elseif($type === 'card-skeleton')
    <div class="card p-6 space-y-4">
        <div class="skeleton h-4 w-3/4 rounded"></div>
        <div class="skeleton h-4 w-1/2 rounded"></div>
        <div class="skeleton h-20 w-full rounded"></div>
        <div class="flex gap-3">
            <div class="skeleton h-8 w-20 rounded-lg"></div>
            <div class="skeleton h-8 w-20 rounded-lg"></div>
        </div>
    </div>
@endif
```

---

## Verification

After creating all component files, verify:
1. All files exist in `resources/views/components/ui/`
2. Run `npm run build` — should complete with no errors
3. No Blade syntax errors — check by visiting any page that uses the layout

## IMPORTANT
- Create the `resources/views/components/ui/` directory first if it doesn't exist
- Each file must be a standalone `.blade.php` file
- Do NOT modify any existing component files in `resources/views/components/` (like `public-header.blade.php`, `footer.blade.php`, etc.) — those will be updated in a later task
