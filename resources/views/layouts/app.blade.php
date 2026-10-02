<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#e8f1f0">
        @include('layouts.partials.app-meta')

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name', 'SEMS') }}</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|jetbrains-mono:500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Chrome fetches the tab bar's pages up front, and any other page as soon as a finger touches its link. --}}
        <script type="speculationrules">
            {"prefetch": [
                {"where": {"and": [{"selector_matches": ".tabrow a.tab, a.tab-action"}, {"not": {"href_matches": "/announcements"}}]}, "eagerness": "eager"},
                {"where": {"and": [{"href_matches": "/*"}, {"not": {"href_matches": ["/logout", "/login/*", "/announcements", "/payslips/*/download", "/attachments/*", "/attendance/*"]}}]}, "eagerness": "moderate"}
            ]}
        </script>
    </head>
    <body class="font-sans antialiased">
        @include('layouts.partials.ambient')
        <x-lightbox />

        <div x-data="{ sidebarOpen: false }" x-effect="document.documentElement.classList.toggle('overflow-hidden', sidebarOpen)" @keydown.escape.window="sidebarOpen = false" class="relative min-h-screen">
            @include('layouts.sidebar')

            <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false" class="fixed left-0 top-0 z-[35] h-viewport w-full bg-slate-900/25 backdrop-blur-md lg:hidden"></div>

            <div class="relative flex min-h-screen flex-col lg:pl-[18rem]">
                @include('layouts.topbar')

                @php
                    $urgentAnnouncement = \App\Models\Announcement::unseenBy(auth()->user())
                        ->where('priority', 'urgent')
                        ->where('created_at', '>=', now()->subDays(3))
                        ->latest()
                        ->first();
                @endphp

                <main class="pb-tabbar flex-1 px-4 pt-3 sm:px-6 sm:pt-6 lg:px-10 lg:pb-10">
                    <div class="mx-auto w-full max-w-7xl">
                        @if ($urgentAnnouncement)
                            <div class="mb-5 flex items-center gap-3 rounded-2xl border border-white/20 bg-gradient-to-r from-rose-500/90 to-rose-400/90 px-4 py-3 text-sm text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.3),0_12px_28px_-12px_rgba(225,29,72,0.6)] backdrop-blur-xl">
                                <span class="relative flex h-2.5 w-2.5 shrink-0">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white/70"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-white"></span>
                                </span>
                                <span class="min-w-0 flex-1 truncate"><strong class="font-semibold">Urgent:</strong> {{ $urgentAnnouncement->title }}</span>
                                <a href="{{ route('announcements.index') }}" class="shrink-0 rounded-lg bg-white/15 px-3 py-1 text-xs font-semibold text-white hover:bg-white/25">View</a>
                                <form method="POST" action="{{ route('announcements.dismiss') }}" class="shrink-0">
                                    @csrf
                                    <button type="submit" class="rounded-lg p-1 text-white/70 hover:bg-white/15 hover:text-white" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4" /></button>
                                </form>
                            </div>
                        @endif

                        @isset($header)
                            <header class="mb-6 animate-fade-up sm:mb-8">
                                {{ $header }}
                            </header>
                        @endisset

                        @if (session('warning'))
                            <div x-data="{ show: true }" x-show="show" x-transition class="alert-warning mb-5 animate-fade-up">
                                <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0" />
                                <span class="flex-1">{{ session('warning') }}</span>
                                <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Dismiss">&times;</button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div x-data="{ show: true }" x-show="show" x-transition class="alert-error mb-5 animate-fade-up">
                                <x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0" />
                                <span class="flex-1">{{ session('error') }}</span>
                                <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Dismiss">&times;</button>
                            </div>
                        @endif

                        {{-- Every form's validation errors are summarised here, so a rejected submit is never silent. --}}
                        <x-validation-alert />

                        @if (session('success'))
                            <div x-data="{ show: true }" x-show="show" x-transition class="alert-success mb-5 animate-fade-up">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                                <span class="flex-1">{{ session('success') }}</span>
                                <button type="button" @click="show = false" class="text-emerald-600/60 hover:text-emerald-800" aria-label="Dismiss">&times;</button>
                            </div>
                        @endif

                        <div class="animate-fade-up">
                            {{ $slot }}
                        </div>
                    </div>
                </main>

                <footer class="hidden px-4 pb-6 text-center text-xs text-slate-400 sm:px-6 lg:block lg:px-10">
                    &copy; {{ now()->year }} {{ config('app.name', 'SEMS') }} &middot; Smart Employee Management System
                </footer>
            </div>

            @include('layouts.tabbar')
        </div>

        {{-- Clock-in countdown for employees who haven't clocked in yet (skipped on the attendance page itself) --}}
        @php
            $shiftReminder = auth()->user()->employee && ! request()->routeIs('employee.attendance.*')
                ? app(\App\Services\ShiftReminderService::class)->forUser(auth()->user())
                : null;
        @endphp
        @if ($shiftReminder && $shiftReminder['state'] === 'upcoming')
            <div x-data="shiftCountdown(@js($shiftReminder))">
                <a href="{{ route('employee.attendance.index') }}" x-show="phase === 'warning'" x-cloak x-transition
                   class="glass fixed bottom-[calc(var(--tabbar-h)+1.5rem+var(--safe-bottom))] right-4 z-[45] flex items-center gap-3 rounded-full py-2 pl-2 pr-4 animate-float sm:right-6 lg:bottom-6"
                   :class="urgent ? '!bg-rose-50/80' : '!bg-amber-50/80'">
                    <span class="relative h-9 w-9">
                        <svg viewBox="0 0 36 36" class="h-9 w-9 -rotate-90">
                            <circle cx="18" cy="18" r="15" fill="none" stroke="rgba(15,23,42,0.08)" stroke-width="3.5" />
                            <circle cx="18" cy="18" r="15" fill="none" stroke-width="3.5" stroke-linecap="round" :stroke="urgent ? '#f43f5e' : '#f59e0b'"
                                    stroke-dasharray="94.25" :stroke-dashoffset="94.25 * (1 - progress)" />
                        </svg>
                        <x-icon name="fingerprint" class="absolute inset-0 m-auto h-4 w-4 text-slate-700" />
                    </span>
                    <span class="leading-tight">
                        <span class="block text-[11px] font-medium text-slate-500">Clock in before <span x-text="r.start_label"></span></span>
                        <span class="block font-mono text-base font-semibold tabular-nums" :class="urgent ? 'text-rose-600' : 'text-amber-700'" x-text="countdown"></span>
                    </span>
                </a>

                <x-shift-overlays :cta-href="route('employee.attendance.index')" />
            </div>
        @endif
    </body>
</html>
