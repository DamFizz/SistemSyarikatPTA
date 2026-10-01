<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>@yield('title') · {{ config('app.name', 'SEMS') }}</title>
        @include('layouts.partials.favicon')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans antialiased">
        <main class="flex min-h-screen items-center justify-center px-5 py-12">
            <div class="card w-full max-w-md rounded-[2rem] p-8 text-center sm:p-10">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl @yield('tone', 'bg-slate-900/5 text-slate-600')">
                    <x-icon :name="trim($__env->yieldContent('icon', 'info'))" class="h-8 w-8" />
                </div>
                <p class="mt-6 font-mono text-sm font-semibold tracking-widest text-slate-400">@yield('code')</p>
                <h1 class="mt-1 text-2xl font-bold">@yield('heading')</h1>
                <p class="muted mt-2 leading-relaxed">@yield('message')</p>
                <div class="mt-8 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                    <button type="button" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')" class="btn-secondary">Go back</button>
                    <a href="{{ url('/dashboard') }}" class="btn-primary">Back to dashboard</a>
                </div>
            </div>
        </main>
    </body>
</html>
