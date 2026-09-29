<x-app-layout title="Dashboard">
    <x-slot name="header">
        <p class="eyebrow">System control</p>
        <h2 class="page-title mt-1">Super Admin Overview</h2>
        <p class="muted mt-1">System-wide overview, office networks and recent activity.</p>
    </x-slot>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Total users" :value="$totalUsers" icon="user" />
        <x-stat-card label="Employees" :value="$totalEmployees" icon="users" tone="sky" :href="route('hr.employees.index')" />
        <x-stat-card label="Departments" :value="$totalDepartments" icon="building" tone="violet" :href="route('hr.departments.index')" />
        <x-stat-card label="Offices / branches" :value="$totalOffices" icon="map-pin" tone="amber" :href="route('super-admin.offices.index')" />
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-5">
        <div class="card lg:col-span-3">
            <div class="card-header">
                <div class="flex items-center gap-2">
                    <x-icon name="wifi" class="h-[18px] w-[18px] text-emerald-500" />
                    <h3 class="card-title">Office WiFi & NFC status</h3>
                </div>
                <a href="{{ route('super-admin.offices.index') }}" class="btn-ghost btn-sm">Manage</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($offices as $office)
                    <li class="flex items-center gap-4 px-5 py-3.5">
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-slate-900">{{ $office->name }}</div>
                            <div class="truncate text-xs text-slate-500">{{ $office->wifi_ssid ? 'SSID: '.$office->wifi_ssid : 'WiFi not configured' }} · {{ $office->employees_count }} staff</div>
                        </div>
                        @if (! $office->network_check_enabled)
                            <span class="chip bg-amber-50 text-amber-700 ring-1 ring-amber-600/15">Testing mode</span>
                        @elseif ($office->isNetworkConfigured())
                            <span class="chip bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/15">Secured</span>
                        @else
                            <span class="chip bg-rose-50 text-rose-700 ring-1 ring-rose-600/15">Needs setup</span>
                        @endif
                        <a href="{{ route('super-admin.offices.network.edit', $office) }}" class="btn-secondary btn-sm">Configure</a>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-slate-400">No offices yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="card-title">Recent activity</h3>
                <a href="{{ route('super-admin.audit-logs.index') }}" class="btn-ghost btn-sm">Audit log</a>
            </div>
            <ol class="relative space-y-4 p-5 before:absolute before:bottom-6 before:left-[27px] before:top-6 before:w-px before:bg-slate-200">
                @forelse ($recentLogs as $log)
                    <li class="relative flex gap-3">
                        <span class="relative z-10 mt-1 h-3 w-3 shrink-0 rounded-full border-2 border-white bg-emerald-500 ring-1 ring-emerald-200"></span>
                        <div class="min-w-0">
                            <div class="text-sm text-slate-700">{{ $log->description ?? $log->action }}</div>
                            <div class="mt-0.5 text-xs text-slate-400">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at?->diffForHumans() }}</div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">No activity recorded yet.</li>
                @endforelse
            </ol>
        </div>
    </div>
</x-app-layout>
