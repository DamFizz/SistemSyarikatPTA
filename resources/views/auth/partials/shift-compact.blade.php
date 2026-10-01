{{-- Phones / tablets: compact reminder above the login form (the brand panel is hidden there) --}}
<div class="mb-4 lg:hidden">
    <button type="button" @click="if (phase === 'warning') { dismissed.sheet = false; sheetOpen = true }"
            class="glass flex w-full items-center gap-3 rounded-[1.5rem] p-3.5 text-left transition active:scale-[0.99]"
            :class="{ '!bg-amber-50/80': phase === 'warning' && !urgent, '!bg-rose-50/80': urgent || phase === 'late' }">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 text-sm font-bold text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.35)]" x-text="r.name.charAt(0)"></span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-slate-900">Hi, <span x-text="r.name"></span></span>
            <span class="block truncate text-xs text-slate-500">
                <template x-if="r.state === 'upcoming' && phase === 'calm'"><span>Clock in at <b class="text-slate-700" x-text="r.start_label"></b> · in <span x-text="untilLabel"></span></span></template>
                <template x-if="phase === 'warning' || phase === 'final'"><span :class="urgent ? 'text-rose-600' : 'text-amber-700'">Clock in before <b x-text="r.start_label"></b> — tap for countdown</span></template>
                <template x-if="phase === 'late'"><span class="text-rose-600">You're <b x-text="lateLabel"></b> late — clock in now</span></template>
                <template x-if="phase === 'ended'"><span>Today's shift has ended</span></template>
                <template x-if="r.state === 'clocked_in'"><span class="text-emerald-700">Clocked in at <b x-text="r.clocked_in_at"></b> ✓</span></template>
                <template x-if="r.state === 'off'"><span x-text="r.off_reason === 'holiday' && r.holiday_name ? ('Public holiday — ' + r.holiday_name) : 'No clock-in needed today'"></span></template>
            </span>
        </span>
        <span class="shrink-0 font-mono text-sm font-semibold tabular-nums"
              :class="phase === 'warning' || phase === 'final' ? (urgent ? 'text-rose-600' : 'text-amber-700') : 'text-slate-700'"
              x-text="phase === 'warning' || phase === 'final' ? countdown : clock.slice(0, 5)"></span>
    </button>
    <form method="POST" action="{{ route('login.forget-device') }}" class="mt-1.5 text-center">
        @csrf
        <button type="submit" class="text-[11px] text-slate-400 hover:text-slate-600">Not <span x-text="r.name"></span>? Forget this device</button>
    </form>
</div>
