<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 shrink-0">
    <button @click="sidebarOpen = ! sidebarOpen" class="lg:hidden text-slate-500 hover:text-slate-700">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <div class="hidden lg:block text-sm text-slate-400">
        {{ now()->format('l, d F Y') }}
    </div>

    <div class="flex items-center gap-4 ms-auto">
        <button class="relative text-slate-500 hover:text-slate-700" title="Notifications">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </button>

        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-2 text-sm">
                    <div class="h-8 w-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-semibold text-xs">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <span class="hidden sm:block text-slate-700 font-medium">{{ Auth::user()->name }}</span>
                    <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>
