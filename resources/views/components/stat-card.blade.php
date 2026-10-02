@props(['label', 'value', 'icon' => null, 'tone' => 'emerald', 'href' => null])

@php
    $tones = [
        'emerald' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        'sky' => 'bg-sky-50 text-sky-600 ring-sky-100',
        'amber' => 'bg-amber-50 text-amber-600 ring-amber-100',
        'rose' => 'bg-rose-50 text-rose-600 ring-rose-100',
        'violet' => 'bg-violet-50 text-violet-600 ring-violet-100',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-200',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card group relative block overflow-hidden p-5'.($href ? ' card-hover' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-[13px] font-medium text-slate-500">{{ $label }}</div>
            <div class="mt-2 text-[1.45rem] font-bold leading-none sm:text-[1.75rem] tracking-tight text-slate-900 tabular-nums" data-countup>{{ $value }}</div>
        </div>
        @if ($icon)
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 transition duration-300 ease-spring group-hover:-rotate-6 group-hover:scale-110 {{ $tones[$tone] ?? $tones['emerald'] }}">
                <x-icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="mt-3 text-xs text-slate-500">{{ $slot }}</div>
    @endif
</{{ $tag }}>
