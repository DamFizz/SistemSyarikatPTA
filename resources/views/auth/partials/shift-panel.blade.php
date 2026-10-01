@php
    $offLabels = [
        'leave' => "You're on leave today — enjoy your day off.",
        'rest_day' => 'Today is your weekly rest day.',
        'holiday' => 'Public holiday — no clock-in needed.',
        'no_shift' => 'No shift is assigned to you today.',
        'inactive' => 'No clock-in needed today.',
    ];
@endphp

{{-- Desktop brand panel: live clock + today's clock-in deadline for the employee who last used this device --}}
<div class="mt-10 max-w-sm rounded-[1.75rem] border border-white/15 bg-white/[0.06] p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.18),0_24px_48px_-20px_rgba(0,0,0,0.6)] backdrop-blur-2xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="text-xs font-medium text-slate-400">Hi, <span class="text-slate-200" x-text="r.name"></span> &middot; {{ now()->format('D, d M') }}</span>

        <span x-show="r.state === 'clocked_in'" class="chip bg-emerald-400/10 text-emerald-300 ring-1 ring-emerald-400/20"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> On duty</span>
        <span x-show="r.state === 'off'" class="chip bg-sky-400/10 text-sky-300 ring-1 ring-sky-400/20">Day off</span>
        <span x-show="phase === 'calm'" class="chip bg-white/10 text-slate-300 ring-1 ring-white/15">Shift today</span>
        <span x-show="phase === 'warning' || phase === 'final'" x-cloak class="chip ring-1" :class="urgent || phase === 'final' ? 'bg-rose-400/15 text-rose-300 ring-rose-400/25' : 'bg-amber-300/15 text-amber-200 ring-amber-300/25'">
            <span class="h-1.5 w-1.5 animate-pulse rounded-full" :class="urgent || phase === 'final' ? 'bg-rose-400' : 'bg-amber-300'"></span> Hurry
        </span>
        <span x-show="phase === 'late'" x-cloak class="chip bg-rose-400/15 text-rose-300 ring-1 ring-rose-400/25">Late</span>
        <span x-show="phase === 'ended'" x-cloak class="chip bg-white/10 text-slate-300 ring-1 ring-white/15">Shift ended</span>
    </div>

    <div class="mt-4 font-mono text-5xl font-medium tracking-tight text-white tabular-nums" x-text="clock">--:--:--</div>

    <p class="mt-1.5 text-sm text-slate-400">
        <template x-if="r.state === 'upcoming'">
            <span>Clock in at <span class="font-semibold text-white" x-text="r.start_label"></span> &middot; <span x-text="r.shift_name"></span></span>
        </template>
        <template x-if="r.state === 'clocked_in'">
            <span>Clocked in at <span class="font-semibold text-emerald-300" x-text="r.clocked_in_at"></span> ✓</span>
        </template>
        <template x-if="r.state === 'off'">
            <span x-text="@js($offLabels)[r.off_reason] || 'No clock-in needed today.'"></span>
        </template>
    </p>

    {{-- Calm: time until the shift --}}
    <div x-show="phase === 'calm'" class="mt-5 rounded-2xl bg-white/[0.05] px-4 py-3 text-sm text-slate-300 ring-1 ring-inset ring-white/10">
        Your shift starts in <span class="font-semibold text-white" x-text="untilLabel"></span>
    </div>

    {{-- Last 10 minutes: countdown --}}
    <div x-show="phase === 'warning' || phase === 'final'" x-cloak class="mt-5 rounded-2xl px-4 py-4 ring-1 ring-inset transition-colors"
         :class="urgent || phase === 'final' ? 'bg-rose-500/15 ring-rose-400/25' : 'bg-amber-400/10 ring-amber-300/20'">
        <div class="flex items-baseline justify-between gap-3">
            <span class="text-sm" :class="urgent || phase === 'final' ? 'text-rose-200' : 'text-amber-100'">Clock in within</span>
            <span class="font-mono text-4xl font-medium tabular-nums" :class="urgent || phase === 'final' ? 'text-rose-200' : 'text-amber-200'" x-text="countdown"></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10">
            <div class="h-full rounded-full transition-[width] duration-300 ease-linear" :class="urgent || phase === 'final' ? 'bg-rose-400' : 'bg-amber-300'" :style="`width: ${progress * 100}%`"></div>
        </div>
    </div>

    {{-- Shift already over --}}
    <div x-show="phase === 'ended'" x-cloak class="mt-5 rounded-2xl bg-white/[0.05] px-4 py-3 text-sm text-slate-300 ring-1 ring-inset ring-white/10">
        Today's shift (<span x-text="r.start_label"></span> – <span x-text="r.end_label"></span>) has ended.
    </div>

    {{-- Missed it --}}
    <div x-show="phase === 'late'" x-cloak class="mt-5 rounded-2xl bg-rose-500/15 px-4 py-3 text-sm text-rose-200 ring-1 ring-inset ring-rose-400/25">
        You're <span class="font-semibold" x-text="lateLabel"></span> late — sign in and clock in now.
    </div>

    <form method="POST" action="{{ route('login.forget-device') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-xs text-slate-500 underline-offset-2 hover:text-slate-300 hover:underline">Not <span x-text="r.name"></span>? Forget this device</button>
    </form>
</div>
