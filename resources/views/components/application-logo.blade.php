@props(['size' => 'h-9 w-9'])

<span {{ $attributes->merge(['class' => "relative inline-flex shrink-0 items-center justify-center rounded-xl bg-ink-800 ring-1 ring-white/10 shadow-lg shadow-emerald-500/10 {$size}"]) }}>
    <svg viewBox="0 0 32 32" class="h-[70%] w-[70%]" aria-hidden="true">
        <defs>
            <linearGradient id="sems-logo-gradient" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#6ee7b7" />
                <stop offset="1" stop-color="#10b981" />
            </linearGradient>
        </defs>
        <path d="M22 9.5c-1.5-1.4-3.5-2.1-5.8-2.1-3.7 0-6.2 2-6.2 4.8 0 6.2 11.4 3.7 11.4 8.2 0 1.6-1.6 2.7-4.2 2.7-2.3 0-4.3-.9-5.7-2.3" fill="none" stroke="url(#sems-logo-gradient)" stroke-width="3.2" stroke-linecap="round" />
    </svg>
</span>
