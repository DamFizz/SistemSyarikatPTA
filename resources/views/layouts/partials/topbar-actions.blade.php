{{-- Bell + account menu, shared by the phone and desktop top bars. --}}
<a href="{{ route('announcements.index') }}" class="lg-press relative rounded-full p-2 text-slate-700 hover:bg-white/40" title="Announcements">
    <x-icon name="bell" />
    @if (\App\Models\Announcement::unseenBy(auth()->user())->where('created_at', '>=', now()->subDays(30))->exists())
        <span class="absolute right-1.5 top-1.5 flex h-2.5 w-2.5">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-70"></span>
            <span class="relative h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-white"></span>
        </span>
    @endif
</a>

<x-dropdown align="right" width="56">
    <x-slot name="trigger">
        <button class="lg-press flex items-center gap-2 rounded-full p-1 {{ ($showName ?? false) ? 'pe-2.5' : '' }} hover:bg-white/40">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 text-xs font-bold text-white shadow-[inset_1px_1px_0.5px_-1px_rgba(255,255,255,0.9),inset_0_0_0_0.5px_rgba(255,255,255,0.4)]">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </span>
            @if ($showName ?? false)
                <span class="max-w-[10rem] truncate text-sm font-medium text-slate-800">{{ Auth::user()->name }}</span>
                <svg class="h-4 w-4 text-slate-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
            @endif
        </button>
    </x-slot>

    <x-slot name="content">
        <div class="border-b border-slate-900/[0.06] px-4 py-3">
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
