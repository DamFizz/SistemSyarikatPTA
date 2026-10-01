@props(['sheet' => false, 'ctaHref' => null, 'ctaLabel' => 'Clock in now'])

{{-- Must be placed inside an element using x-data="shiftCountdown(...)". --}}
@php
    $cta = $ctaHref
        ? 'href="'.e($ctaHref).'"'
        : 'href="#" @click.prevent="dismiss(phase === \'final\' ? \'final\' : \'sheet\'); $nextTick(() => document.getElementById(\'email\')?.focus())"';
@endphp

@if ($sheet)
    {{-- Phone: full-screen warning for the last 10 minutes --}}
    <div x-show="sheetOpen" x-cloak x-transition.opacity.duration.300ms
         class="fixed inset-0 z-[60] flex flex-col bg-ink-950/85 px-6 pb-10 pt-6 text-white backdrop-blur-2xl lg:hidden"
         role="alertdialog" aria-modal="true" aria-label="Clock-in reminder">
        <div class="flex items-center justify-between">
            <span class="chip bg-white/10 text-white/80 ring-1 ring-white/15">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full" :class="urgent ? 'bg-rose-400' : 'bg-amber-300'"></span>
                Clock-in reminder
            </span>
            <button type="button" @click="dismiss('sheet')" class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white ring-1 ring-white/15 transition active:scale-95" aria-label="Close reminder">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center text-center">
            <p class="text-lg text-white/70">Hi <span class="font-semibold text-white" x-text="r.name"></span>, clock in before</p>
            <p class="mt-1 text-2xl font-bold" x-text="r.start_label"></p>

            <div class="relative mt-10 h-64 w-64">
                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                    <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="6" />
                    <circle cx="60" cy="60" r="54" fill="none" stroke-width="6" stroke-linecap="round"
                            :stroke="urgent ? '#fb7185' : '#fcd34d'"
                            stroke-dasharray="339.3" :stroke-dashoffset="339.3 * (1 - progress)" style="transition: stroke-dashoffset .25s linear" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="font-mono text-6xl font-medium tabular-nums" :class="urgent ? 'text-rose-300' : 'text-amber-200'" x-text="countdown"></span>
                    <span class="mt-2 text-sm text-white/60">left to clock in</span>
                </div>
            </div>

            <p class="mt-8 max-w-xs text-sm leading-relaxed text-white/60">You'll be marked late after this. Tap the NFC tag, then clock in with a selfie.</p>
        </div>

        <a {!! $cta !!} class="btn-primary btn-lg w-full">{{ $ctaLabel }} <x-icon name="arrow-right" class="h-5 w-5" /></a>
    </div>
@endif

{{-- Final 10 seconds: big countdown over everything, for anyone who still hasn't clocked in --}}
<div x-show="finalOpen" x-cloak x-transition.opacity.duration.200ms
     class="fixed inset-0 z-[70] flex flex-col items-center justify-center bg-rose-950/70 px-6 text-white backdrop-blur-2xl"
     role="alertdialog" aria-modal="true" aria-live="assertive" aria-label="Final seconds to clock in">
    <button type="button" @click="dismiss('final')" class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20 transition active:scale-95" aria-label="Close countdown">
        <x-icon name="x" class="h-5 w-5" />
    </button>

    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-rose-200">Clock in now</p>

    <div class="relative mt-8 flex h-56 w-56 items-center justify-center sm:h-72 sm:w-72">
        <span class="absolute inset-0 rounded-full border-2 border-rose-300/50 animate-ripple"></span>
        <span class="absolute inset-4 rounded-full bg-white/10 ring-1 ring-white/20 backdrop-blur-xl"></span>
        <span class="relative font-mono text-[7.5rem] font-semibold leading-none tabular-nums sm:text-[9rem]" x-text="finalNumber"></span>
    </div>

    <p class="mt-8 text-center text-base text-white/80">
        <span x-text="r.name"></span>, your shift starts at <span class="font-semibold text-white" x-text="r.start_label"></span>
    </p>

    <a {!! $cta !!} class="btn-primary btn-lg mt-8 w-full max-w-xs">{{ $ctaLabel }} <x-icon name="arrow-right" class="h-5 w-5" /></a>
</div>
