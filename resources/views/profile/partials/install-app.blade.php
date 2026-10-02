{{--
 | Install SEMS as an app (PWA). Chrome / Edge show their own install dialog; other
 | browsers get the exact steps for the device they are on.
 --}}
<section x-data="installApp" class="flex flex-col gap-5 sm:flex-row sm:items-start">
    <img src="{{ asset('icons/icon-192.png') }}" alt="" class="h-16 w-16 shrink-0 rounded-[1.1rem] shadow-lg shadow-emerald-900/20">

    <div class="min-w-0 flex-1">
        <h2 class="text-lg font-semibold text-slate-900">Install the SEMS app</h2>
        <p class="mt-1 text-sm text-slate-600">
            Open SEMS from your home screen or desktop like a real app: full screen, no browser bars, one tap to clock in.
        </p>

        {{-- Already running as the installed app --}}
        <div x-show="installed" x-cloak class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3.5 py-2 text-sm font-semibold text-emerald-700">
            <x-icon name="check-circle" class="h-5 w-5" /> Installed: you're using the SEMS app
        </div>

        {{-- Chrome / Edge: the real install dialog --}}
        <div x-show="! installed && canPrompt" x-cloak class="mt-4">
            <button type="button" @click="install()" :disabled="busy" class="btn-primary">
                <x-icon name="device" class="h-4 w-4" />
                <span x-text="busy ? 'Opening…' : 'Install app'">Install app</span>
            </button>
        </div>

        {{-- Everyone else (or after "Not now"): step-by-step for this device --}}
        <div x-show="! installed && ! canPrompt" x-cloak class="glass-inset mt-4 p-4 text-sm text-slate-700">
            <p x-show="declined" class="mb-2 text-xs text-slate-500">No problem. You can still install it any time:</p>

            <template x-if="platform === 'ios'">
                <ol class="list-decimal space-y-1.5 ps-5">
                    <li>Open this page in <strong>Safari</strong>.</li>
                    <li>Tap the <strong>Share</strong> button <span class="whitespace-nowrap">(square with an arrow ↑)</span>.</li>
                    <li>Choose <strong>Add to Home Screen</strong>, then <strong>Add</strong>.</li>
                </ol>
            </template>
            <template x-if="platform === 'android'">
                <ol class="list-decimal space-y-1.5 ps-5">
                    <li>Tap the <strong>⋮</strong> menu at the top right of Chrome.</li>
                    <li>Choose <strong>Install app</strong> (or <strong>Add to Home screen</strong>).</li>
                    <li>Tap <strong>Install</strong>. SEMS appears with your apps.</li>
                </ol>
            </template>
            <template x-if="platform === 'samsung'">
                <ol class="list-decimal space-y-1.5 ps-5">
                    <li>Tap the <strong>≡</strong> menu at the bottom right.</li>
                    <li>Choose <strong>Add page to</strong> → <strong>Home screen</strong>.</li>
                </ol>
            </template>
            <template x-if="platform === 'android-firefox'">
                <ol class="list-decimal space-y-1.5 ps-5">
                    <li>Tap the <strong>⋮</strong> menu, then <strong>Install</strong> (or <strong>Add to Home screen</strong>).</li>
                </ol>
            </template>
            <template x-if="platform === 'desktop-chrome'">
                <ol class="list-decimal space-y-1.5 ps-5">
                    <li>Click the <strong>install icon</strong> at the right end of the address bar, or</li>
                    <li>open the <strong>⋮</strong> menu → <strong>Cast, save and share</strong> → <strong>Install page as app</strong>.</li>
                </ol>
            </template>
            <template x-if="platform === 'desktop-edge'">
                <ol class="list-decimal space-y-1.5 ps-5">
                    <li>Open the <strong>…</strong> menu → <strong>Apps</strong> → <strong>Install this site as an app</strong>.</li>
                </ol>
            </template>
            <template x-if="platform === 'desktop-safari'">
                <ol class="list-decimal space-y-1.5 ps-5">
                    <li>In the menu bar choose <strong>File</strong> → <strong>Add to Dock</strong>.</li>
                </ol>
            </template>
            <template x-if="platform === 'desktop-firefox'">
                <p>Firefox can't install web apps. Open SEMS in <strong>Chrome</strong> or <strong>Edge</strong> and install it from there.</p>
            </template>
        </div>
    </div>
</section>
