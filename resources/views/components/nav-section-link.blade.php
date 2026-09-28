@props(['active' => false, 'icon' => null])

@php
$classes = $active
    ? 'bg-emerald-500/10 text-emerald-400 font-medium'
    : 'text-slate-300 hover:bg-slate-800 hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => "flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition $classes"]) }}>
    @if ($icon)
        <span class="shrink-0">{{ $icon }}</span>
    @endif
    <span>{{ $slot }}</span>
</a>
