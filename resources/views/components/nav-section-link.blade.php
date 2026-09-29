@props(['active' => false, 'icon' => null])

<a {{ $attributes->merge(['class' => 'nav-link'.($active ? ' nav-link-active' : '')]) }} @if ($active) aria-current="page" @endif>
    @if ($icon)
        <x-icon :name="$icon" :class="'h-[19px] w-[19px] shrink-0 '.($active ? 'text-emerald-400' : 'text-slate-500')" />
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
