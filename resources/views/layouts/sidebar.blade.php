@php
    $user = auth()->user();
    $role = $user->role;

    $groups = \App\Support\Navigation::groupsFor($user);
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="[view-transition-name:sidebar] fixed left-0 top-0 z-40 hidden h-viewport lg:flex w-[17rem] max-w-[85vw] flex-col overscroll-contain bg-white/80 text-slate-600 shadow-[var(--lg-specular),var(--lg-float)] backdrop-blur-2xl backdrop-saturate-[1.8] transition-transform duration-300 ease-out lg:inset-y-3 lg:left-3 lg:h-auto lg:max-w-none lg:translate-x-0 lg:rounded-[2rem] lg:bg-white/25 lg:backdrop-blur-md lg:backdrop-saturate-[2]"
    data-refract
>

    <div class="relative flex h-20 shrink-0 items-center gap-3 px-6">
        <x-application-logo />
        <div class="leading-tight">
            <div class="text-[15px] font-bold tracking-tight text-slate-900">SEMS</div>
            <div class="text-[11px] text-slate-400">Smart Employee Management</div>
        </div>
        <button @click="sidebarOpen = false" class="ms-auto rounded-full p-1.5 text-slate-400 hover:bg-white/70 hover:text-slate-900 lg:hidden" aria-label="Close menu">
            <x-icon name="x" />
        </button>
    </div>

    <nav class="relative flex-1 space-y-0.5 overflow-y-auto overscroll-contain px-3 pb-4 [scrollbar-width:thin] [scrollbar-color:rgba(15,23,42,0.12)_transparent]">
        @foreach ($groups as $label => $items)
            @if ($label)
                <div class="nav-section">{{ $label }}</div>
            @endif
            @foreach ($items as [$text, $routeName, $patterns, $icon])
                <x-nav-section-link :href="route($routeName)" :active="request()->routeIs(...$patterns)" :icon="$icon">
                    {{ $text }}
                </x-nav-section-link>
            @endforeach
        @endforeach
    </nav>

    <div class="glass-thin relative m-3 rounded-2xl p-3">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 text-sm font-bold text-white">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-[13px] font-semibold text-slate-900">{{ $user->name }}</div>
                <div class="truncate text-[11px] capitalize text-emerald-600">{{ str_replace('_', ' ', $role) }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-full p-2 text-slate-400 transition hover:bg-rose-500/10 hover:text-rose-600" title="Log out">
                    <x-icon name="logout" class="h-[18px] w-[18px]" />
                </button>
            </form>
        </div>
    </div>
</aside>
