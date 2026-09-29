<header class="sticky top-0 z-20 px-4 pt-3 sm:px-6 lg:px-10">
    <div class="mx-auto flex h-14 max-w-7xl items-center gap-3 rounded-2xl border border-white/60 bg-white/70 px-3 shadow-soft backdrop-blur-xl sm:px-4">
        <button @click="sidebarOpen = ! sidebarOpen" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800 lg:hidden" aria-label="Open menu">
            <x-icon name="menu" />
        </button>

        <div class="flex min-w-0 items-center gap-2 text-sm">
            <span class="hidden text-slate-400 sm:inline">SEMS</span>
            <span class="hidden text-slate-300 sm:inline">/</span>
            <span class="truncate font-semibold text-slate-800">{{ $title ?? 'Dashboard' }}</span>
        </div>

        <div class="ms-auto flex items-center gap-1.5 sm:gap-2">
            <div x-data="{ now: new Date() }" x-init="setInterval(() => now = new Date(), 1000)"
                 class="hidden items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-3 py-1.5 text-xs text-slate-500 md:flex">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                <span x-text="now.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short' })">{{ now()->format('D, d M') }}</span>
                <span class="font-mono font-medium text-slate-800" x-text="now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' })">{{ now()->format('H:i:s') }}</span>
            </div>

            <a href="{{ route('announcements.index') }}" class="relative rounded-xl p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800" title="Announcements">
                <x-icon name="bell" />
            </a>

            <x-dropdown align="right" width="56">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2 rounded-xl p-1 pe-2 hover:bg-slate-100">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-400 to-teal-600 text-xs font-bold text-white">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                        <span class="hidden max-w-[10rem] truncate text-sm font-medium text-slate-700 sm:block">{{ Auth::user()->name }}</span>
                        <svg class="h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <div class="truncate text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</div>
                    </div>
                    <x-dropdown-link :href="route('profile.edit')">
                        <x-icon name="user" class="h-4 w-4 text-slate-400" /> {{ __('My Profile') }}
                    </x-dropdown-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            <x-icon name="logout" class="h-4 w-4 text-slate-400" /> {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>
