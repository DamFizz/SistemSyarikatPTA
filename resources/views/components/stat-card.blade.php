@props(['label', 'value'])

<div class="bg-white rounded-xl border border-slate-200 p-5">
    <div class="text-sm text-slate-500">{{ $label }}</div>
    <div class="mt-2 text-2xl font-semibold text-slate-800">{{ $value }}</div>
    @if ($slot->isNotEmpty())
        <div class="mt-1 text-xs text-emerald-600">{{ $slot }}</div>
    @endif
</div>
