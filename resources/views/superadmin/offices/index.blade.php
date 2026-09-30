<x-app-layout title="Offices & WiFi">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">System</p>
                <h2 class="page-title mt-1">Offices &amp; Branches</h2>
                <p class="muted mt-1">{{ $offices->total() }} location(s) · geofence, WiFi and NFC settings for attendance.</p>
            </div>
            <a href="{{ route('super-admin.offices.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add branch</a>
        </div>
    </x-slot>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($offices as $office)
            <div class="card card-hover flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-ink-900 text-emerald-400"><x-icon name="building" /></span>
                    @if (! $office->network_check_enabled)
                        <span class="chip bg-amber-50 text-amber-700 ring-1 ring-amber-600/15">Testing mode</span>
                    @elseif ($office->isNetworkConfigured())
                        <span class="chip bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/15"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Network locked</span>
                    @else
                        <span class="chip bg-rose-50 text-rose-700 ring-1 ring-rose-600/15"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Needs WiFi setup</span>
                    @endif
                </div>

                <h3 class="mt-4 text-base font-semibold">{{ $office->name }}</h3>
                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $office->address }}</p>

                <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="glass-inset !rounded-xl px-2 py-2.5">
                        <dt class="text-[11px] text-slate-400">Radius</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $office->allowed_radius_meters }}m</dd>
                    </div>
                    <div class="glass-inset !rounded-xl px-2 py-2.5">
                        <dt class="text-[11px] text-slate-400">Staff</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $office->employees_count }}</dd>
                    </div>
                    <div class="glass-inset !rounded-xl px-2 py-2.5">
                        <dt class="text-[11px] text-slate-400">WiFi</dt>
                        <dd class="truncate text-sm font-semibold text-slate-900" title="{{ $office->wifi_ssid }}">{{ $office->wifi_ssid ?: '—' }}</dd>
                    </div>
                </dl>

                <a href="https://www.google.com/maps?q={{ $office->latitude }},{{ $office->longitude }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1.5 font-mono text-xs text-slate-400 hover:text-emerald-700">
                    <x-icon name="map-pin" class="h-3.5 w-3.5" /> {{ $office->latitude }}, {{ $office->longitude }}
                </a>

                <div class="mt-5 flex gap-2 border-t border-slate-900/[0.06] pt-4">
                    <a href="{{ route('super-admin.offices.network.edit', $office) }}" class="btn-dark btn-sm flex-1"><x-icon name="wifi" class="h-4 w-4" /> WiFi &amp; NFC</a>
                    <a href="{{ route('super-admin.offices.edit', $office) }}" class="btn-secondary btn-sm flex-1"><x-icon name="pencil" class="h-4 w-4" /> Edit location</a>
                </div>
            </div>
        @empty
            <div class="card col-span-full p-12 text-center text-slate-400">No offices yet. Add your first branch.</div>
        @endforelse
    </div>

    <div class="mt-5">{{ $offices->links() }}</div>
</x-app-layout>
