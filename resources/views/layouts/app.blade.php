<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name', 'SEMS') }}</title>

        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%2310b981'/%3E%3Ctext x='12' y='17' font-family='Arial,sans-serif' font-size='14' font-weight='bold' fill='white' text-anchor='middle'%3ES%3C/text%3E%3C/svg%3E">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-800">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">

            @include('layouts.sidebar')

            <!-- Mobile sidebar overlay -->
            <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

            <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
                @include('layouts.topbar')

                @php
                    $urgentAnnouncement = \App\Models\Announcement::where('priority', 'urgent')
                        ->where(function ($q) {
                            $q->whereNull('department_id')->orWhere('department_id', auth()->user()->employee?->department_id);
                        })
                        ->where('created_at', '>=', now()->subDays(3))
                        ->latest()
                        ->first();
                @endphp
                @if ($urgentAnnouncement)
                    <div class="bg-red-600 text-white px-4 py-2 text-sm text-center">
                        <strong>URGENT:</strong> {{ $urgentAnnouncement->title }} —
                        <a href="{{ route('announcements.index') }}" class="underline">View details</a>
                    </div>
                @endif

                @isset($header)
                    <header class="bg-white border-b border-slate-200">
                        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    <div class="max-w-7xl mx-auto w-full">
                        @if (session('success'))
                            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                                {{ session('success') }}
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
