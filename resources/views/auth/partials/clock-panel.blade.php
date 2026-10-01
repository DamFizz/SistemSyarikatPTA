@php $holiday = \App\Models\PublicHoliday::forDate(today()); @endphp

{{-- Shown when this device hasn't signed in yet: a real live clock, never sample data. --}}
<div x-data="{
        now: new Date(),
        offset: {{ (int) round(microtime(true) * 1000) }} - Date.now(),
        init() { setInterval(() => this.now = new Date(Date.now() + this.offset), 1000) },
        get time() { return this.now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) },
        get greeting() { const h = this.now.getHours(); return h >= 5 && h < 12 ? 'Good morning' : (h >= 12 && h < 17 ? 'Good afternoon' : 'Good evening') },
     }"
     class="mt-10 max-w-sm rounded-[1.75rem] border border-white/15 bg-white/[0.06] p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.18),0_24px_48px_-20px_rgba(0,0,0,0.6)] backdrop-blur-2xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="text-xs font-medium text-slate-400" x-text="greeting">Hello</span>
        <span class="text-xs text-slate-400">{{ now()->format('D, d M Y') }}</span>
    </div>

    <div class="mt-4 font-mono text-5xl font-medium tracking-tight text-white tabular-nums" x-text="time">{{ now()->format('H:i:s') }}</div>

    @if ($holiday)
        <div class="mt-5 flex items-center gap-2 rounded-2xl bg-sky-400/10 px-4 py-3 text-sm text-sky-200 ring-1 ring-inset ring-sky-300/20">
            <x-icon name="sparkles" class="h-4 w-4 shrink-0" />
            <span>Public holiday today — <span class="font-semibold text-white">{{ $holiday->name }}</span></span>
        </div>
    @endif

    <p class="mt-5 flex items-start gap-2 text-xs leading-relaxed text-slate-400">
        <x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-400" />
        Sign in once on this device to see your clock-in time and a reminder countdown here.
    </p>
</div>
