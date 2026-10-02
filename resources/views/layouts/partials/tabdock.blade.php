{{--
 | iOS 26 style dock: a floating glass capsule of tabs plus a separate round action.
 | "More" morphs the capsule into the full menu: the panel is revealed from the capsule's
 | outline (clip-path + spring transform, no layout per frame) and tiles pop in.
 --}}
<div class="tabdock {{ $centre ? '' : 'no-action' }}" :class="menu && 'menu-open'"
     x-data="{
         menu: false,
         {{-- The panel is exactly as tall as its content (capped by CSS). --}}
         fit() {
             const panel = this.$refs.menu;
             panel.style.height = 'auto';
             const h = panel.offsetHeight;
             panel.style.height = '';
             panel.style.setProperty('--menu-h', h + 'px');
         },
         {{-- Picking an item: the tile pops, the menu springs shut, then the page changes. --}}
         leaving: false,
         go(event, then) {
             if (this.leaving || event.metaKey || event.ctrlKey || event.shiftKey) return;
             event.preventDefault();
             this.leaving = true;
             event.currentTarget.classList.add('tile-picked');
             navigator.vibrate?.(8);
             setTimeout(() => { this.menu = false; }, 140);
             setTimeout(then, 520);
         },
     }"
     x-init="fit()" @resize.window.debounce.200ms="fit()"
     x-effect="document.documentElement.classList.toggle('overflow-hidden', menu)"
     @keydown.escape.window="menu = false"
     @pageshow.window="menu = false; leaving = false">
    <div class="menu-scrim" x-show="menu" x-cloak x-transition.opacity.duration.250ms @click="menu = false" aria-hidden="true"></div>

    {{-- Full menu (revealed out of the tab bar) --}}
    <div class="menupanel" x-ref="menu" :aria-hidden="(! menu).toString()" role="dialog" aria-label="Menu">
        <div class="flex items-center gap-3 px-5 pb-3 pt-5">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 text-sm font-bold text-white shadow-[var(--lg-specular)]">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0 flex-1 leading-tight">
                <div class="truncate text-[15px] font-semibold text-slate-900">{{ $user->name }}</div>
                <div class="truncate text-xs capitalize text-emerald-700">{{ str_replace('_', ' ', $role) }}</div>
            </div>
            <button type="button" @click="menu = false" class="lg-press flex h-9 w-9 items-center justify-center rounded-full bg-white/60 text-slate-500 shadow-[var(--lg-specular)]" aria-label="Close menu">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 pb-3 [scrollbar-width:none]">
            @php $d = 0; @endphp
            @foreach ($groups as $section => $links)
                @if ($section)
                    <div class="px-2 pb-1.5 pt-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500/80">{{ $section }}</div>
                @endif
                <div class="grid grid-cols-4 gap-x-1 gap-y-2">
                    @foreach ($links as [$label, $routeName, $patterns, $icon])
                        @php $on = request()->routeIs(...$patterns); @endphp
                        <a href="{{ route($routeName) }}" class="tile {{ $on ? 'tile-active' : '' }}" style="--d: {{ $d++ }}"
                           @click="go($event, () => location.assign($el.href))" @if ($on) aria-current="page" @endif>
                            <span class="tile-icon"><x-icon :name="$icon" class="h-6 w-6" /></span>
                            <span class="line-clamp-2">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="flex gap-2 border-t border-slate-900/[0.06] p-3">
            <a href="{{ route('profile.edit') }}" class="btn-secondary flex-1" @click="go($event, () => location.assign($el.href))"><x-icon name="user" class="h-4 w-4" /> Profile</a>
            <form method="POST" action="{{ route('logout') }}" class="flex-1" @submit="go($event, () => $el.submit())">
                @csrf
                <button type="submit" class="btn-danger-soft w-full"><x-icon name="logout" class="h-4 w-4" /> Log out</button>
            </form>
        </div>
    </div>

    <nav class="tabbar" data-refract aria-label="Main" style="--tabs: {{ count($items) }}; --tab-index: {{ $activeIndex === false ? 0 : $activeIndex }}">
        {{-- Tabs --}}
        <div class="tabrow">
            @if ($activeIndex !== false)
                <span class="tab-indicator"></span>
            @endif

            @foreach ($items as $i => [$label, $routeName, $patterns, $icon])
                @if ($routeName === null)
                    <button type="button" @click="menu = true; navigator.vibrate?.(8)" class="tab" :aria-expanded="menu.toString()">
                        <x-icon :name="$icon" class="h-[22px] w-[22px]" />
                        <span>{{ $label }}</span>
                    </button>
                @else
                    <a href="{{ route($routeName) }}" class="tab {{ $activeIndex === $i ? 'tab-active' : '' }}" onclick="navigator.vibrate?.(8)"
                       @if ($activeIndex === $i) aria-current="page" @endif>
                        <x-icon :name="$icon" class="h-[22px] w-[22px]" />
                        <span>{{ $label }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </nav>

    @if ($centre)
        <a href="{{ route($centre[1]) }}" class="tab-action {{ $centreActive ? 'tab-action-active' : '' }}" data-refract
           onclick="navigator.vibrate?.(10)" aria-label="Attendance — clock in or out" @if ($centreActive) aria-current="page" @endif>
            <x-icon :name="$centre[3]" class="h-7 w-7 shrink-0" />
        </a>
    @endif
</div>
