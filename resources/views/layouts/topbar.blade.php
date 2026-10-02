{{-- Content blurs away as it slides under the floating controls. --}}
<div class="scroll-edge" aria-hidden="true"></div>

<header class="sticky top-0 z-20 px-4 pb-2 pt-[max(0.75rem,env(safe-area-inset-top))] sm:px-6 lg:px-10">
    {{-- Phones (iOS 26): separate floating glass controls, compact title in between. --}}
    <div class="flex h-12 items-center gap-3 lg:hidden">
        <a href="{{ route('dashboard') }}" class="lg lg-press flex h-12 w-12 shrink-0 items-center justify-center rounded-full [view-transition-name:bar-home]" data-refract aria-label="Home">
            <x-application-logo size="h-8 w-8" />
        </a>

        <div class="bar-title min-w-0 flex-1 truncate text-center text-[15px] font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</div>

        <div class="lg flex h-12 shrink-0 items-center gap-0.5 rounded-full px-1.5 [view-transition-name:bar-actions]" data-refract>
            @include('layouts.partials.topbar-actions')
        </div>
    </div>

    {{-- Larger screens: one glass capsule. --}}
    <div class="lg mx-auto hidden h-14 max-w-7xl items-center gap-3 rounded-full px-3 [view-transition-name:bar-desktop] lg:flex" data-refract>
        <div class="flex min-w-0 items-center gap-2 ps-2 text-sm">
            <span class="text-slate-500">SEMS</span>
            <span class="text-slate-400">/</span>
            <span class="truncate font-semibold text-slate-900">{{ $title ?? 'Dashboard' }}</span>
        </div>

        <div class="ms-auto flex items-center gap-2">
            <div x-data="{ now: new Date() }" x-init="setInterval(() => now = new Date(), 1000)"
                 class="hidden items-center gap-2 rounded-full bg-white/30 px-3 py-1.5 text-xs text-slate-600 shadow-[var(--lg-specular)] xl:flex">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                <span x-text="now.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short' })">{{ now()->format('D, d M') }}</span>
                <span class="font-mono font-medium text-slate-900" x-text="now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' })">{{ now()->format('H:i:s') }}</span>
            </div>

            @include('layouts.partials.topbar-actions', ['showName' => true])
        </div>
    </div>
</header>
