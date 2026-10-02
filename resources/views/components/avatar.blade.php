{{-- A person's profile photo, or their initial on the brand gradient when they have none. --}}
@props(['user' => null, 'size' => 'h-9 w-9', 'text' => 'text-sm'])

@php
    $url = $user?->avatarUrl();
    $initial = $user?->initial() ?? '?';
@endphp

@if ($url)
    <img src="{{ $url }}" alt="{{ $user->name }}" loading="lazy" decoding="async"
         {{ $attributes->merge(['class' => "{$size} shrink-0 rounded-full object-cover bg-white/60 shadow-[inset_0_0_0_0.5px_rgba(255,255,255,0.6)]"]) }}>
@else
    <span {{ $attributes->merge(['class' => "{$size} {$text} flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 font-bold text-white shadow-[inset_1px_1px_0.5px_-1px_rgba(255,255,255,0.9),inset_0_0_0_0.5px_rgba(255,255,255,0.4)]"]) }}
          aria-hidden="true">{{ $initial }}</span>
@endif
