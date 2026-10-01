<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0a1020">

        <title>{{ config('app.name', 'SEMS') }} · Sign in</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|jetbrains-mono:500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-screen" @if ($reminder ?? null) x-data="shiftCountdown(@js($reminder), { sheet: true })" @endif>
            {{-- Brand panel --}}
            <aside class="relative hidden w-[46%] max-w-2xl overflow-hidden bg-ink-900 lg:flex lg:flex-col">
                <div class="absolute inset-0 opacity-[0.35]" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.08) 1px, transparent 0); background-size: 26px 26px;"></div>
                <div class="absolute -top-32 -left-24 h-96 w-96 rounded-full bg-emerald-500/25 blur-3xl"></div>
                <div class="absolute -bottom-40 right-0 h-96 w-96 rounded-full bg-teal-400/15 blur-3xl"></div>

                <div class="relative flex items-center gap-3 px-12 pt-12">
                    <x-application-logo size="h-10 w-10" />
                    <span class="text-lg font-bold tracking-tight text-white">SEMS</span>
                </div>

                <div class="relative flex flex-1 flex-col justify-center px-12 xl:px-16">
                    <p class="eyebrow !text-emerald-400">Smart Employee Management</p>
                    <h1 class="mt-4 text-4xl font-extrabold leading-[1.1] !text-white xl:text-5xl">
                        Your whole workforce,<br>
                        <span class="bg-gradient-to-r from-emerald-300 to-teal-200 bg-clip-text text-transparent">one calm workspace.</span>
                    </h1>
                    <p class="mt-5 max-w-md text-[15px] leading-relaxed text-slate-400">
                        Attendance, leave, overtime, payroll and helpdesk — verified, audited and beautifully simple.
                    </p>

                    @if ($reminder ?? null)
                        @include('auth.partials.shift-panel')
                    @else
                        @include('auth.partials.clock-panel')
                    @endif
                </div>

                <div class="relative px-12 pb-10 text-xs text-slate-500">&copy; {{ now()->year }} SEMS &middot; Final Year Project</div>
            </aside>

            {{-- Form panel --}}
            <main class="relative flex min-w-0 flex-1 flex-col items-center justify-center px-5 py-12 sm:px-8">

                <div class="relative w-full max-w-[26rem]">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <x-application-logo size="h-10 w-10" />
                        <span class="text-lg font-bold tracking-tight text-slate-900">SEMS</span>
                    </div>

                    @if ($reminder ?? null)
                        @include('auth.partials.shift-compact')
                    @endif

                    <div class="card rounded-[2rem] p-7 sm:p-9">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            @if ($reminder ?? null)
                <x-shift-overlays :sheet="true" cta-label="Sign in now" />
            @endif
        </div>
    </body>
</html>
