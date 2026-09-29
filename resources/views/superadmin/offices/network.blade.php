<x-app-layout title="WiFi & NFC">
    <x-slot name="header">
        <a href="{{ route('super-admin.offices.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
            <x-icon name="arrow-left" class="h-4 w-4" /> Offices
        </a>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">{{ $office->name }}</p>
                <h2 class="page-title mt-1">Office WiFi &amp; NFC</h2>
                <p class="muted mt-1">The NFC tag connects staff phones to this WiFi. Attendance is only accepted from this office's internet connection.</p>
            </div>
            @if (! $office->network_check_enabled)
                <span class="chip bg-amber-50 text-amber-700 ring-1 ring-amber-600/20"><x-icon name="warning" class="h-3.5 w-3.5" /> Testing mode active</span>
            @elseif ($office->isNetworkConfigured())
                <span class="chip bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20"><x-icon name="shield" class="h-3.5 w-3.5" /> Network lock active</span>
            @else
                <span class="chip bg-rose-50 text-rose-700 ring-1 ring-rose-600/20"><x-icon name="warning" class="h-3.5 w-3.5" /> Not configured</span>
            @endif
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="alert-error mb-5">
            <x-icon name="warning" class="h-5 w-5 shrink-0" />
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-5">
        <form method="POST" action="{{ route('super-admin.offices.network.update', $office) }}" class="space-y-5 lg:col-span-3"
              x-data="{
                  security: @js(old('wifi_security', $office->wifi_security ?? 'WPA')),
                  ips: @js(old('allowed_ips', $office->allowed_ips ?? '')),
                  testing: @js((bool) old('testing_mode', ! $office->network_check_enabled)),
                  showPassword: false,
                  currentIp: @js($currentIp),
                  addCurrentIp() {
                      const list = this.ips.split(/[\s,;]+/).filter(Boolean);
                      if (!list.includes(this.currentIp)) list.push(this.currentIp);
                      this.ips = list.join('\n');
                  },
              }">
            @csrf
            @method('PUT')

            {{-- WiFi credentials --}}
            <div class="card p-6 sm:p-7">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><x-icon name="wifi" /></span>
                    <div>
                        <h3 class="card-title">WiFi network</h3>
                        <p class="text-xs text-slate-500">Written into the NFC tag so phones join automatically.</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="wifi_ssid" value="Network name (SSID)" />
                        <x-text-input id="wifi_ssid" name="wifi_ssid" class="mt-1.5 block w-full" :value="old('wifi_ssid', $office->wifi_ssid)" required maxlength="32" placeholder="e.g. HQ-Staff" />
                        <x-input-error :messages="$errors->get('wifi_ssid')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="wifi_security" value="Security" />
                        <select id="wifi_security" name="wifi_security" x-model="security" class="input mt-1.5 block w-full">
                            @foreach (\App\Models\Office::WIFI_SECURITY_TYPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="security !== 'nopass'">
                        <x-input-label for="wifi_password" value="WiFi password" />
                        <div class="relative mt-1.5">
                            <input id="wifi_password" name="wifi_password" :type="showPassword ? 'text' : 'password'" class="input block w-full pe-11" autocomplete="new-password"
                                   placeholder="{{ $office->wifi_password ? '•••••••• (unchanged)' : 'Enter password' }}">
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 end-0 flex items-center px-3.5 text-slate-400 hover:text-slate-600"><x-icon name="eye" class="h-[18px] w-[18px]" /></button>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">Stored encrypted. Leave blank to keep the current password.</p>
                        <x-input-error :messages="$errors->get('wifi_password')" class="mt-1.5" />
                    </div>
                </div>
            </div>

            {{-- Network lock --}}
            <div class="card p-6 sm:p-7">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600"><x-icon name="lock" /></span>
                    <div>
                        <h3 class="card-title">Network lock</h3>
                        <p class="text-xs text-slate-500">Public IP address(es) of this office's internet connection.</p>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <x-input-label for="allowed_ips" value="Allowed public IPs / ranges" />
                        <button type="button" @click="addCurrentIp()" class="btn-success-soft btn-sm">
                            <x-icon name="plus" class="h-3.5 w-3.5" /> Add my current IP (<span class="font-mono" x-text="currentIp"></span>)
                        </button>
                    </div>
                    <textarea id="allowed_ips" name="allowed_ips" x-model="ips" rows="4" class="input mt-2 block w-full font-mono text-[13px]" placeholder="175.139.12.34&#10;60.50.0.0/24"></textarea>
                    <p class="mt-1.5 text-xs text-slate-400">One per line. Supports single IPs and CIDR ranges (IPv4 &amp; IPv6).</p>
                    <x-input-error :messages="$errors->get('allowed_ips')" class="mt-1.5" />

                    <div class="mt-4 flex items-start gap-3 rounded-2xl p-3.5 text-sm {{ $currentIpAllowed ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-50 text-slate-600' }}">
                        <x-icon :name="$currentIpAllowed ? 'check-circle' : 'info'" class="mt-0.5 h-5 w-5 shrink-0" />
                        <div>
                            Your device is currently on <span class="font-mono font-semibold">{{ $currentIp }}</span>
                            — {{ $currentIpAllowed ? 'this network is allowed.' : 'not in the allowed list.' }}
                            <div class="mt-0.5 text-xs opacity-75">To register the office network, connect this device to the office WiFi and click “Add my current IP”.</div>
                        </div>
                    </div>
                </div>

                <label class="mt-6 flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition"
                       :class="testing ? 'border-amber-300 bg-amber-50/70' : 'border-slate-200 hover:border-slate-300'">
                    <input type="hidden" name="testing_mode" value="0">
                    <input type="checkbox" name="testing_mode" value="1" x-model="testing" class="mt-0.5">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Testing mode (temporary)</div>
                        <div class="mt-0.5 text-xs leading-relaxed text-slate-500">Skips the office WiFi check so you can test clock-in from any network. GPS and selfie are still required, and every record is flagged for review. Turn off before going live.</div>
                    </div>
                </label>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('super-admin.offices.index') }}" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-primary"><x-icon name="check" class="h-4 w-4" /> Save network settings</button>
            </div>
        </form>

        {{-- Setup guide --}}
        <div class="space-y-5 lg:col-span-2">
            <div class="surface-dark p-6">
                <div class="relative">
                    <div class="flex items-center gap-2 text-sm font-semibold text-white"><x-icon name="signal" class="h-5 w-5 text-emerald-400" /> Write the NFC tag</div>
                    <ol class="mt-4 space-y-3 text-sm text-slate-400">
                        @foreach ([
                            'Install the free <strong class="text-slate-200">NFC Tools</strong> app (Android / iOS).',
                            'Go to <strong class="text-slate-200">Write → Add a record → Wi-Fi network</strong>.',
                            'Enter SSID <strong class="font-mono text-emerald-300">'.e($office->wifi_ssid ?: '—').'</strong>, choose WPA2-Personal and the same password.',
                            'Tap <strong class="text-slate-200">Write</strong> and hold your phone on the tag (NTAG213/215/216).',
                            'Stick the tag at the entrance. Staff tap it → phone joins WiFi → SEMS unlocks the Clock button.',
                        ] as $i => $step)
                            <li class="flex gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-white">{{ $i + 1 }}</span>
                                <span>{!! $step !!}</span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-5 rounded-xl bg-white/5 p-3 text-xs leading-relaxed text-slate-400">iPhones cannot auto-join WiFi from an NFC tag — print the QR code below for them (scan with the Camera app).</p>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <h3 class="card-title">WiFi QR poster</h3>
                    @if ($wifiQrSvg)
                        <button type="button" onclick="window.print()" class="btn-ghost btn-sm"><x-icon name="printer" class="h-4 w-4" /> Print</button>
                    @endif
                </div>
                @if ($wifiQrSvg)
                    <div class="mt-4 flex flex-col items-center rounded-2xl border border-dashed border-slate-200 p-5 print:border-0">
                        <div class="rounded-2xl bg-white p-3 shadow-soft [&>svg]:h-48 [&>svg]:w-48">{!! $wifiQrSvg !!}</div>
                        <div class="mt-3 text-sm font-semibold text-slate-900">{{ $office->wifi_ssid }}</div>
                        <div class="text-xs text-slate-500">Scan with your camera to join the office WiFi</div>
                    </div>
                @else
                    <p class="muted mt-3">Save the WiFi details to generate a QR code.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
