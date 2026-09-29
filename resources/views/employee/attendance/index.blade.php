@php
    $config = [
        'state' => $state,
        'statusUrl' => route('employee.attendance.status'),
        'beginUrl' => route('employee.attendance.begin'),
        'clockInUrl' => route('employee.attendance.clock-in'),
        'clockOutUrl' => route('employee.attendance.clock-out'),
        'office' => [
            'lat' => (float) $office->latitude,
            'lng' => (float) $office->longitude,
            'radius' => (int) $office->allowed_radius_meters,
        ],
        'ssid' => $office->wifi_ssid,
        'networkCheck' => (bool) $office->network_check_enabled,
        'networkConfigured' => $office->isNetworkConfigured(),
    ];
@endphp

<x-app-layout title="Attendance">
    <x-slot name="header">
        <p class="eyebrow">{{ $office->name }}</p>
        <h2 class="page-title mt-1">Attendance</h2>
        <p class="muted mt-1">Tap the office NFC tag, then clock in or out with a live selfie.</p>
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-5">
        {{-- ============ Attendance console ============ --}}
        <div class="lg:col-span-3" x-data="attendanceConsole(@js($config))">
            <div class="surface-dark min-h-[30rem] p-5 sm:p-8">
                <div class="relative">
                    {{-- Top row: live clock + network pill --}}
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="font-mono text-4xl font-medium tracking-tight text-white sm:text-5xl" x-text="clock">{{ now()->format('H:i:s') }}</div>
                            <div class="mt-1 text-sm text-slate-400" x-text="dateLabel">{{ now()->format('l, d F Y') }}</div>
                        </div>
                        <div class="chip ring-1"
                             :class="onNetwork ? 'bg-emerald-400/10 text-emerald-300 ring-emerald-400/25' : 'bg-white/5 text-slate-400 ring-white/10'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="onNetwork ? 'bg-emerald-400' : 'bg-slate-500 animate-pulse'"></span>
                            <span x-text="onNetwork ? (config.networkCheck ? 'Office WiFi connected' : 'Testing mode') : 'Not on office WiFi'"></span>
                        </div>
                    </div>

                    <div x-show="error" x-cloak x-transition class="mt-6 flex items-start gap-3 rounded-2xl border border-rose-400/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                        <x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0 text-rose-300" />
                        <span class="flex-1" x-text="error"></span>
                        <button type="button" @click="error = ''" class="text-rose-300/70 hover:text-white">&times;</button>
                    </div>

                    @if (! $office->network_check_enabled)
                        <div class="mt-6 flex items-start gap-3 rounded-2xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-200">
                            <x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0" />
                            <span>Testing mode is on — the office WiFi check is temporarily disabled by the administrator. Records are flagged for review.</span>
                        </div>
                    @endif

                    {{-- ---------- Phase: completed ---------- --}}
                    <div x-show="phase === 'completed'" x-cloak class="flex flex-col items-center py-14 text-center">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-emerald-400/10 ring-1 ring-emerald-400/30">
                            <x-icon name="check" class="h-10 w-10 text-emerald-300" />
                        </div>
                        <h3 class="mt-5 text-xl font-semibold !text-white">You're done for today</h3>
                        <p class="mt-1 text-sm text-slate-400">Clock-in and clock-out are both recorded. See you tomorrow!</p>
                    </div>

                    {{-- ---------- Phase: waiting for the office network ---------- --}}
                    <div x-show="phase === 'network'" x-cloak class="flex flex-col items-center py-10 text-center">
                        @if ($office->network_check_enabled && ! $office->isNetworkConfigured())
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-rose-500/10 ring-1 ring-rose-400/30">
                                <x-icon name="wifi" class="h-9 w-9 text-rose-300" />
                            </div>
                            <h3 class="mt-5 text-xl font-semibold !text-white">Office WiFi not set up yet</h3>
                            <p class="mt-1 max-w-sm text-sm text-slate-400">Your administrator hasn't configured the office network for attendance. Please contact them.</p>
                        @else
                            {{-- Phone + NFC ripple illustration --}}
                            <div class="relative flex h-44 w-44 items-center justify-center">
                                <span class="absolute inset-0 rounded-full border border-emerald-400/40 animate-ripple"></span>
                                <span class="absolute inset-0 rounded-full border border-emerald-400/30 animate-ripple [animation-delay:0.7s]"></span>
                                <span class="absolute inset-0 rounded-full border border-emerald-400/20 animate-ripple [animation-delay:1.4s]"></span>
                                <div class="relative flex h-24 w-24 items-center justify-center rounded-[1.75rem] bg-gradient-to-br from-emerald-400 to-teal-600 shadow-glow">
                                    <x-icon name="signal" class="h-11 w-11 text-white" />
                                </div>
                            </div>
                            <h3 class="mt-4 text-xl font-semibold !text-white sm:text-2xl">Tap your phone on the NFC tag</h3>
                            <p class="mt-2 max-w-md text-sm leading-relaxed text-slate-400">
                                Your phone will join <span class="font-semibold text-slate-200">{{ $office->wifi_ssid ?: 'the office WiFi' }}</span> automatically.
                                Accept the “Connect” prompt — this page detects the office network by itself.
                            </p>

                            <div class="mt-6 flex items-center gap-2 text-xs text-slate-500">
                                <svg class="h-4 w-4 animate-spin text-emerald-400" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-20"/><path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                <span x-text="checking ? 'Checking network…' : 'Waiting for office WiFi…'"></span>
                            </div>

                            <button type="button" @click="checkNetwork(true)" class="mt-5 inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10">
                                <x-icon name="refresh" class="h-4 w-4" /> I'm connected — check again
                            </button>

                            <details class="mt-6 w-full max-w-md rounded-2xl border border-white/5 bg-white/[0.03] px-4 py-3 text-left text-sm text-slate-400">
                                <summary class="cursor-pointer select-none font-medium text-slate-300">Phone has no NFC, or using an iPhone?</summary>
                                <ul class="mt-3 list-disc space-y-1.5 pl-5">
                                    <li>Open WiFi settings and join <strong class="text-slate-200">{{ $office->wifi_ssid ?: 'the office network' }}</strong> manually, or scan the WiFi QR poster at the office with your camera.</li>
                                    <li>Turn off VPN / Private Relay — it hides the office network.</li>
                                    <li>Make sure mobile data isn't forced on (e.g. “WiFi assist”).</li>
                                </ul>
                                <p class="mt-3 text-xs text-slate-500">Current network IP: <span class="font-mono" x-text="ip || '…'"></span></p>
                            </details>
                        @endif
                    </div>

                    {{-- ---------- Phase: ready (single context-aware button) ---------- --}}
                    <div x-show="phase === 'ready'" x-cloak class="flex flex-col items-center py-10 text-center">
                        <div class="flex items-center gap-2 text-sm text-emerald-300">
                            <x-icon name="wifi" class="h-5 w-5" />
                            <span x-text="config.networkCheck ? ('Connected to ' + (config.ssid || 'office WiFi')) : 'Network check skipped (testing mode)'"></span>
                        </div>

                        <button type="button" @click="start()" :disabled="busy"
                                class="group relative mt-8 flex h-48 w-48 flex-col items-center justify-center rounded-full text-white transition duration-300 hover:scale-[1.03] active:scale-95 disabled:opacity-60 sm:h-52 sm:w-52"
                                :class="action === 'in'
                                    ? 'bg-gradient-to-br from-emerald-400 to-teal-600 shadow-[0_20px_60px_-12px_rgba(16,185,129,0.65)]'
                                    : 'bg-gradient-to-br from-rose-400 to-orange-500 shadow-[0_20px_60px_-12px_rgba(244,63,94,0.6)]'">
                            <span class="absolute inset-2 rounded-full border border-white/25"></span>
                            <x-icon name="fingerprint" class="h-12 w-12 transition group-hover:scale-110" />
                            <span class="mt-2 text-xl font-bold tracking-tight" x-text="action === 'in' ? 'Clock In' : 'Clock Out'"></span>
                            <span class="mt-0.5 text-xs text-white/80" x-show="busy" x-cloak>Starting…</span>
                        </button>

                        <p class="mt-7 text-sm text-slate-400" x-text="action === 'in' ? 'Next: location check and a live selfie.' : 'Clock-out also needs your location and a live selfie.'"></p>
                    </div>

                    {{-- ---------- Phase: capture (GPS + selfie) ---------- --}}
                    <div x-show="phase === 'capture'" x-cloak class="mt-6">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <div class="font-semibold text-white" x-text="action === 'in' ? 'Clock-in verification' : 'Clock-out verification'"></div>
                            <div class="chip bg-white/5 font-mono text-slate-300 ring-1 ring-white/10">
                                <x-icon name="lock" class="h-3.5 w-3.5" /> <span x-text="countdownLabel"></span>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-5">
                            {{-- Camera --}}
                            <div class="sm:col-span-3">
                                <div class="relative aspect-[3/4] overflow-hidden rounded-3xl bg-ink-950 ring-1 ring-white/10 sm:aspect-square">
                                    <video x-ref="video" autoplay playsinline muted class="h-full w-full scale-x-[-1] object-cover" x-show="!selfie"></video>
                                    <img :src="selfie" x-show="selfie" x-cloak class="h-full w-full scale-x-[-1] object-cover" alt="Selfie preview">

                                    {{-- Face guide --}}
                                    <div x-show="!selfie && cameraReady" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                                        <div class="h-[62%] w-[52%] rounded-[50%] border-2 border-dashed border-white/50 shadow-[0_0_0_9999px_rgba(6,10,19,0.45)]"></div>
                                    </div>
                                    <div x-show="!cameraReady && !selfie" class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center text-sm text-slate-400">
                                        <x-icon name="camera" class="h-9 w-9 text-slate-500" />
                                        <span x-text="cameraError || 'Starting front camera…'"></span>
                                        <button type="button" x-show="cameraError" @click="startCamera()" class="rounded-xl bg-white/10 px-4 py-2 text-xs font-semibold text-white hover:bg-white/15">Try camera again</button>
                                    </div>
                                    <canvas x-ref="canvas" class="hidden"></canvas>
                                </div>
                            </div>

                            {{-- Checks --}}
                            <div class="space-y-3 sm:col-span-2">
                                <template x-for="item in checklist" :key="item.label">
                                    <div class="flex items-start gap-3 rounded-2xl border border-white/5 bg-white/[0.03] p-3.5">
                                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                                              :class="{ 'bg-emerald-400/15 text-emerald-300': item.state === 'ok', 'bg-rose-400/15 text-rose-300': item.state === 'fail', 'bg-white/5 text-slate-400': item.state === 'wait' }">
                                            <svg x-show="item.state === 'ok'" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.58l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                            <svg x-show="item.state === 'fail'" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
                                            <svg x-show="item.state === 'wait'" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-slate-200" x-text="item.label"></div>
                                            <div class="text-xs text-slate-500" x-text="item.detail"></div>
                                        </div>
                                    </div>
                                </template>

                                <button type="button" x-show="locationState === 'fail'" x-cloak @click="locate()" class="w-full rounded-xl bg-white/10 px-4 py-2 text-xs font-semibold text-white hover:bg-white/15">Retry location</button>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                            <button type="button" @click="cancel()" class="rounded-xl px-4 py-2.5 text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-white">Cancel</button>

                            <div class="flex gap-2">
                                <button type="button" x-show="!selfie" @click="capture()" :disabled="!cameraReady"
                                        class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-ink-900 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
                                    <x-icon name="camera" class="h-4 w-4" /> Take selfie
                                </button>
                                <button type="button" x-show="selfie" x-cloak @click="retake()" :disabled="busy" class="rounded-xl border border-white/10 px-4 py-2.5 text-sm font-medium text-slate-200 hover:bg-white/5">Retake</button>
                                <button type="button" x-show="selfie" x-cloak @click="submit()" :disabled="busy || locationState !== 'ok'"
                                        class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-40"
                                        :class="action === 'in' ? 'bg-emerald-500 hover:bg-emerald-400' : 'bg-rose-500 hover:bg-rose-400'">
                                    <svg x-show="busy" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                                    <span x-text="busy ? 'Submitting…' : (action === 'in' ? 'Confirm clock in' : 'Confirm clock out')"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ---------- Phase: success ---------- --}}
                    <div x-show="phase === 'success'" x-cloak class="flex flex-col items-center py-14 text-center">
                        <div class="relative flex h-24 w-24 items-center justify-center">
                            <span class="absolute inset-0 rounded-full bg-emerald-400/20 animate-ripple"></span>
                            <div class="relative flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 shadow-glow">
                                <x-icon name="check" class="h-10 w-10 text-white" />
                            </div>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold !text-white" x-text="doneAction === 'in' ? 'Clocked in!' : 'Clocked out!'"></h3>
                        <p class="mt-2 max-w-sm text-sm text-slate-400" x-text="successMessage"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Side column ============ --}}
        <div class="space-y-5 lg:col-span-2">
            <div class="card p-5">
                <h3 class="card-title">Today</h3>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="text-xs font-medium text-slate-500">Clock in</div>
                        <div class="mt-1 font-mono text-2xl text-slate-900">{{ $today?->clock_in_time?->format('H:i') ?? '--:--' }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="text-xs font-medium text-slate-500">Clock out</div>
                        <div class="mt-1 font-mono text-2xl text-slate-900">{{ $today?->clock_out_time?->format('H:i') ?? '--:--' }}</div>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between rounded-2xl border border-slate-100 px-4 py-3 text-sm">
                    <span class="text-slate-500">Status</span>
                    @if ($today)
                        <x-status-badge :status="$today->status" />
                    @else
                        <span class="text-slate-400">Not started</span>
                    @endif
                </div>
                @if ($today?->working_minutes)
                    <div class="mt-3 flex items-center justify-between rounded-2xl border border-slate-100 px-4 py-3 text-sm">
                        <span class="text-slate-500">Worked</span>
                        <span class="font-semibold text-slate-900">{{ intdiv($today->working_minutes, 60) }}h {{ $today->working_minutes % 60 }}m</span>
                    </div>
                @endif
            </div>

            <div class="card p-5">
                <div class="flex items-center gap-2">
                    <x-icon name="shield" class="h-5 w-5 text-emerald-500" />
                    <h3 class="card-title">How your attendance is verified</h3>
                </div>
                <ul class="mt-4 space-y-3.5 text-sm">
                    @foreach ([
                        ['wifi', 'Office WiFi', 'Tapping the NFC tag connects you to the office network; only that network is accepted.'],
                        ['map-pin', 'GPS geofence', 'You must be within '.$office->allowed_radius_meters.' m of '.$office->name.'.'],
                        ['camera', 'Live selfie', 'Taken with the front camera for both clock-in and clock-out. Re-used photos are rejected.'],
                        ['lock', 'One-time session', 'Each clock action expires after 3 minutes and can only be used once.'],
                        ['device', 'Registered device', 'Your first phone is registered. Other devices are flagged for HR review.'],
                    ] as [$icon, $title, $text])
                        <li class="flex gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><x-icon :name="$icon" class="h-4 w-4" /></span>
                            <div>
                                <div class="font-medium text-slate-800">{{ $title }}</div>
                                <div class="text-xs leading-relaxed text-slate-500">{{ $text }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- ============ History ============ --}}
    <div class="card mt-5 overflow-x-auto">
        <div class="card-header">
            <h3 class="card-title">Recent attendance</h3>
            <span class="text-xs text-slate-400">Last 14 records</span>
        </div>
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Clock in</th>
                    <th>Clock out</th>
                    <th>Worked</th>
                    <th>Selfies</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $record)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-slate-900">{{ $record->attendance_date->format('d M Y') }}</div>
                            <div class="text-xs text-slate-400">{{ $record->attendance_date->format('l') }}</div>
                        </td>
                        <td class="px-4 py-3 font-mono">{{ $record->clock_in_time?->format('H:i') ?? '–' }}</td>
                        <td class="px-4 py-3 font-mono">{{ $record->clock_out_time?->format('H:i') ?? '–' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $record->working_minutes ? intdiv($record->working_minutes, 60).'h '.($record->working_minutes % 60).'m' : '–' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex -space-x-2">
                                @foreach (['in' => $record->selfie_path, 'out' => $record->clock_out_selfie_path] as $type => $path)
                                    @if ($path)
                                        <a href="{{ route('attendance.selfie', [$record, $type]) }}" target="_blank" class="block h-8 w-8 overflow-hidden rounded-lg ring-2 ring-white" title="Clock-{{ $type }} selfie">
                                            <img src="{{ route('attendance.selfie', [$record, $type]) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3"><x-status-badge :status="$record->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-400">No attendance history yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        function attendanceConsole(config) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const rad = (d) => d * Math.PI / 180;
            const distance = (lat1, lng1, lat2, lng2) => {
                const x = Math.sin(rad(lat2 - lat1) / 2) ** 2 + Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(rad(lng2 - lng1) / 2) ** 2;
                return 2 * 6371000 * Math.asin(Math.sqrt(x));
            };
            const IDLE_PHASES = ['network', 'ready'];

            return {
                config,
                state: config.state,
                phase: 'network',
                onNetwork: false,
                checking: false,
                ip: '',
                busy: false,
                error: '',
                clock: '',
                dateLabel: '',
                pollTimer: null,
                countdownTimer: null,
                expiresAt: null,
                countdownLabel: '3:00',
                challenge: null,
                stream: null,
                cameraReady: false,
                cameraError: '',
                selfie: null,
                position: null,
                locationState: 'wait',
                locationDetail: 'Getting your GPS position…',
                successMessage: '',
                doneAction: 'in',

                get action() {
                    return this.state === 'clocked_in' ? 'out' : 'in';
                },

                get checklist() {
                    return [
                        { label: 'Office network', state: 'ok', detail: config.networkCheck ? (config.ssid || 'Office WiFi') + ' verified' : 'Testing mode' },
                        { label: 'Location', state: this.locationState, detail: this.locationDetail },
                        { label: 'Live selfie', state: this.selfie ? 'ok' : 'wait', detail: this.selfie ? 'Captured' : 'Look at the camera and tap “Take selfie”' },
                    ];
                },

                init() {
                    this.tick();
                    setInterval(() => this.tick(), 1000);

                    if (this.state === 'completed') {
                        this.phase = 'completed';
                        return;
                    }

                    this.checkNetwork();
                    document.addEventListener('visibilitychange', () => { if (!document.hidden) this.checkNetwork(); });
                    window.addEventListener('online', () => this.checkNetwork());
                    navigator.connection?.addEventListener?.('change', () => setTimeout(() => this.checkNetwork(), 800));
                },

                tick() {
                    const now = new Date();
                    this.clock = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    this.dateLabel = now.toLocaleDateString('en-GB', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
                },

                schedulePoll() {
                    clearTimeout(this.pollTimer);
                    if (!IDLE_PHASES.includes(this.phase)) return;
                    this.pollTimer = setTimeout(() => this.checkNetwork(), this.onNetwork ? 8000 : 3000);
                },

                async checkNetwork(manual = false) {
                    if (!IDLE_PHASES.includes(this.phase) || this.checking) return;
                    this.checking = true;
                    try {
                        const res = await fetch(config.statusUrl, { headers: { Accept: 'application/json' }, cache: 'no-store', credentials: 'same-origin' });
                        if (res.status === 401 || res.status === 419) { window.location.reload(); return; }
                        if (!res.ok) throw new Error('status ' + res.status);
                        const data = await res.json();
                        this.ip = data.ip;
                        this.state = data.state;
                        this.onNetwork = data.on_office_network;

                        if (data.state === 'completed') {
                            this.phase = 'completed';
                        } else {
                            this.phase = this.onNetwork ? 'ready' : 'network';
                            if (manual && !this.onNetwork) {
                                this.error = 'Still not on the office WiFi. Tap the NFC tag and accept the WiFi prompt.';
                            } else if (this.onNetwork && manual) {
                                this.error = '';
                            }
                        }
                    } catch (e) {
                        // The phone is probably switching networks (joining the office WiFi) — retry shortly.
                        this.onNetwork = false;
                        if (this.phase === 'ready') this.phase = 'network';
                    } finally {
                        this.checking = false;
                        this.schedulePoll();
                    }
                },

                async post(url, body) {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        credentials: 'same-origin',
                        body: JSON.stringify(body),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const first = data.errors ? Object.values(data.errors)[0][0] : null;
                        throw new Error(first || (res.status === 429 ? 'Too many attempts. Please wait a minute.' : data.message) || 'Something went wrong. Please try again.');
                    }
                    return data;
                },

                async start() {
                    this.error = '';
                    this.busy = true;
                    try {
                        const data = await this.post(config.beginUrl, { action: this.action });
                        clearTimeout(this.pollTimer);
                        this.challenge = data.challenge;
                        this.expiresAt = Date.now() + data.expires_in * 1000;
                        this.selfie = null;
                        this.position = null;
                        this.phase = 'capture';
                        this.startCountdown();
                        this.locate();
                        this.$nextTick(() => this.startCamera());
                    } catch (e) {
                        this.error = e.message;
                        this.checkNetwork();
                    } finally {
                        this.busy = false;
                    }
                },

                startCountdown() {
                    clearInterval(this.countdownTimer);
                    const update = () => {
                        const left = Math.max(0, Math.round((this.expiresAt - Date.now()) / 1000));
                        this.countdownLabel = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
                        if (left === 0 && this.phase === 'capture' && !this.busy) {
                            this.cancel();
                            this.error = 'Verification session expired. Please start again.';
                        }
                    };
                    update();
                    this.countdownTimer = setInterval(update, 1000);
                },

                locate() {
                    this.locationState = 'wait';
                    this.locationDetail = 'Getting your GPS position…';
                    if (!navigator.geolocation) {
                        this.locationState = 'fail';
                        this.locationDetail = 'GPS is not available on this device.';
                        return;
                    }
                    navigator.geolocation.getCurrentPosition((pos) => {
                        this.position = pos.coords;
                        const meters = Math.round(distance(pos.coords.latitude, pos.coords.longitude, config.office.lat, config.office.lng));
                        const accuracy = Math.round(pos.coords.accuracy);
                        if (meters > config.office.radius) {
                            this.locationState = 'fail';
                            this.locationDetail = `${meters} m away — must be within ${config.office.radius} m.`;
                        } else {
                            this.locationState = 'ok';
                            this.locationDetail = `${meters} m from office (±${accuracy} m)`;
                        }
                    }, (err) => {
                        this.locationState = 'fail';
                        this.locationDetail = err.code === 1 ? 'Location permission denied. Allow it in browser settings.' : 'Unable to get location: ' + err.message;
                    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
                },

                async startCamera() {
                    this.stopCamera();
                    this.cameraReady = false;
                    this.cameraError = '';
                    if (!navigator.mediaDevices?.getUserMedia) {
                        this.cameraError = 'Camera is not supported in this browser. Use Chrome or Safari over HTTPS.';
                        return;
                    }
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { ideal: 'user' }, width: { ideal: 720 }, height: { ideal: 720 } },
                            audio: false,
                        });
                        const video = this.$refs.video;
                        video.srcObject = this.stream;
                        await video.play().catch(() => {});
                        this.cameraReady = true;
                    } catch (e) {
                        this.cameraError = e.name === 'NotAllowedError'
                            ? 'Camera permission was blocked. Allow camera access for this site in your browser settings, then try again.'
                            : 'Could not start the front camera (' + e.name + ').';
                    }
                },

                stopCamera() {
                    this.stream?.getTracks().forEach((t) => t.stop());
                    this.stream = null;
                },

                capture() {
                    const video = this.$refs.video;
                    if (!this.cameraReady || !video.videoWidth) return;
                    const scale = Math.min(1, 720 / Math.max(video.videoWidth, video.videoHeight));
                    const canvas = this.$refs.canvas;
                    canvas.width = Math.round(video.videoWidth * scale);
                    canvas.height = Math.round(video.videoHeight * scale);
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.selfie = canvas.toDataURL('image/jpeg', 0.85);
                    this.stopCamera();
                    this.cameraReady = false;
                },

                retake() {
                    this.selfie = null;
                    this.startCamera();
                },

                cancel() {
                    clearInterval(this.countdownTimer);
                    this.stopCamera();
                    this.selfie = null;
                    this.challenge = null;
                    this.phase = this.onNetwork ? 'ready' : 'network';
                    this.checkNetwork();
                },

                async submit() {
                    if (!this.selfie || !this.position || this.busy) return;
                    this.busy = true;
                    this.error = '';
                    try {
                        const data = await this.post(this.action === 'in' ? config.clockInUrl : config.clockOutUrl, {
                            challenge: this.challenge,
                            latitude: this.position.latitude,
                            longitude: this.position.longitude,
                            accuracy: this.position.accuracy,
                            selfie: this.selfie,
                        });
                        clearInterval(this.countdownTimer);
                        this.doneAction = this.action;
                        this.successMessage = data.message;
                        this.phase = 'success';
                        setTimeout(() => window.location.assign(data.redirect), 2200);
                    } catch (e) {
                        // The one-time session is consumed on every attempt, so start over.
                        this.error = e.message;
                        clearInterval(this.countdownTimer);
                        this.selfie = null;
                        this.challenge = null;
                        this.phase = 'network';
                        this.checkNetwork();
                    } finally {
                        this.busy = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
