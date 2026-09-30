<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0a1020">

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name', 'SEMS') }}</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|jetbrains-mono:500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: false }" x-effect="document.documentElement.classList.toggle('overflow-hidden', sidebarOpen)" @keydown.escape.window="sidebarOpen = false" class="relative min-h-screen">
            @include('layouts.sidebar')

            <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false" class="fixed left-0 top-0 z-30 h-viewport w-full bg-slate-900/25 backdrop-blur-md lg:hidden"></div>

            <div class="relative flex min-h-screen flex-col lg:pl-[18rem]">
                @include('layouts.topbar')

                @php
                    $urgentAnnouncement = \App\Models\Announcement::unseenBy(auth()->user())
                        ->where('priority', 'urgent')
                        ->where('created_at', '>=', now()->subDays(3))
                        ->latest()
                        ->first();
                @endphp

                <main class="flex-1 px-4 pb-10 pt-4 sm:px-6 sm:pt-6 lg:px-10">
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

                <footer class="px-4 pb-6 text-center text-xs text-slate-400 sm:px-6 lg:px-10">
                    &copy; {{ now()->year }} {{ config('app.name', 'SEMS') }} &middot; Smart Employee Management System
                </footer>
            </div>
        </div>
    </body>
</html>
