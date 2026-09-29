@props(['summary'])

@php
    $weekPct = $summary['week_limit'] > 0 ? min(100, $summary['week_hours'] / $summary['week_limit'] * 100) : 0;
    $otPct = $summary['ot_cap'] > 0 ? min(100, $summary['ot_used'] / $summary['ot_cap'] * 100) : 0;
    $bar = fn (float $pct) => $pct >= 100 ? 'from-rose-400 to-rose-600' : ($pct >= 80 ? 'from-amber-300 to-amber-500' : 'from-emerald-400 to-teal-500');
    $hours = fn (float $h) => rtrim(rtrim(number_format($h, 1, '.', ''), '0'), '.');
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-center gap-2">
        <x-icon name="clock" class="h-5 w-5 text-emerald-500" />
        <h3 class="card-title">Working hours limits</h3>
    </div>

    <div class="mt-4 space-y-4 text-sm">
        <div>
            <div class="flex items-baseline justify-between gap-3">
                <span class="text-slate-600">This week</span>
                <span class="tabular-nums text-slate-500"><span class="font-semibold text-slate-900">{{ $hours($summary['week_hours']) }}h</span> / {{ $hours($summary['week_limit']) }}h</span>
            </div>
            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-gradient-to-r {{ $bar($weekPct) }}" style="width: {{ $weekPct }}%"></div>
            </div>
        </div>

        <div>
            <div class="flex items-baseline justify-between gap-3">
                <span class="text-slate-600">Overtime · {{ now()->format('F') }}</span>
                @if ($summary['ot_cap_applies'])
                    <span class="tabular-nums text-slate-500"><span class="font-semibold text-slate-900">{{ $hours($summary['ot_used']) }}h</span> / {{ $hours($summary['ot_cap']) }}h</span>
                @else
                    <span class="chip bg-slate-100 text-slate-600">Exempt · {{ $hours($summary['ot_used']) }}h</span>
                @endif
            </div>
            @if ($summary['ot_cap_applies'])
                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-gradient-to-r {{ $bar($otPct) }}" style="width: {{ $otPct }}%"></div>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-between gap-3 rounded-2xl border px-3.5 py-2.5
            {{ $summary['rest_day_required'] ? 'border-rose-200 bg-rose-50 text-rose-700' : ($summary['consecutive_days'] >= 5 ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-100 text-slate-600') }}">
            <span>Days worked in a row</span>
            <span class="font-semibold">{{ $summary['consecutive_days'] }} / {{ \App\Services\WorkHoursService::MAX_CONSECUTIVE_DAYS }}</span>
        </div>
        @if ($summary['rest_day_required'])
            <p class="text-xs leading-relaxed text-rose-600">Today should be your weekly rest day. If you must work, you'll be asked to explain why to HR when you clock in.</p>
        @elseif ($summary['consecutive_days'] >= 5)
            <p class="text-xs leading-relaxed text-amber-700">Plan your rest day — you must take one day off after {{ \App\Services\WorkHoursService::MAX_CONSECUTIVE_DAYS }} working days.</p>
        @endif
    </div>
</div>
