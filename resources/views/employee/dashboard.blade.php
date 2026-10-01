@php
    $hour = now()->hour;
    $greeting = $hour >= 5 && $hour < 12 ? 'Good morning' : ($hour >= 12 && $hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = str(Auth::user()->name)->before(' ');
@endphp

<x-app-layout title="Dashboard">
    <x-slot name="header">
        <p class="eyebrow">{{ now()->format('l, d F Y') }}</p>
        <h2 class="page-title mt-1">{{ $greeting }}, {{ $firstName }} 👋</h2>
        <p class="muted mt-1">{{ $employee?->position }} &middot; {{ $employee?->department?->name }}</p>
    </x-slot>

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Today hero --}}
        <div class="surface-dark p-6 sm:p-7 lg:col-span-2">
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Today's attendance</div>
                    @if ($todayAttendance?->clock_out_time)
                        <div class="mt-3 flex items-center gap-2 text-emerald-300"><x-icon name="check-circle" /> <span class="font-semibold">Day completed</span></div>
                        <div class="mt-2 font-mono text-3xl text-white">{{ $todayAttendance->clock_in_time->format('H:i') }} <span class="text-slate-500">→</span> {{ $todayAttendance->clock_out_time->format('H:i') }}</div>
                        <div class="mt-1 text-sm text-slate-400">{{ intdiv($todayAttendance->working_minutes ?? 0, 60) }}h {{ ($todayAttendance->working_minutes ?? 0) % 60 }}m worked</div>
                    @elseif ($todayAttendance?->clock_in_time)
                        <div class="mt-3 flex items-center gap-2 text-emerald-300">
                            <span class="relative flex h-2.5 w-2.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-70"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"></span></span>
                            <span class="font-semibold">On duty</span>
                        </div>
                        <div class="mt-2 font-mono text-4xl text-white">{{ $todayAttendance->clock_in_time->format('H:i') }}</div>
                        <div class="mt-1 text-sm text-slate-400">Clocked in &middot; {{ str($todayAttendance->status)->replace('_', ' ')->title() }}</div>
                    @else
                        <div class="mt-3 text-2xl font-semibold text-white">You haven't clocked in yet</div>
                        <div class="mt-1 text-sm text-slate-400">Tap the office NFC tag to connect, then clock in with a selfie.</div>
                    @endif
                </div>

                <a href="{{ route('employee.attendance.index') }}" class="btn-primary btn-lg shrink-0 self-start sm:self-center">
                    <x-icon name="fingerprint" />
                    {{ $todayAttendance?->clock_in_time && ! $todayAttendance?->clock_out_time ? 'Clock Out' : ($todayAttendance?->clock_out_time ? 'View Attendance' : 'Clock In') }}
                </a>
            </div>
        </div>

        <x-stat-card label="Open helpdesk tickets" :value="$pendingTickets" icon="lifebuoy" tone="sky" :href="route('employee.tickets.index')">
            Tickets awaiting resolution
        </x-stat-card>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
        <x-stat-card label="Days present this month" :value="$monthPresent" icon="check-badge" />
        <x-stat-card label="Late arrivals this month" :value="$monthLate" icon="clock" tone="amber" />
        <x-stat-card label="Hours worked this month" :value="intdiv($monthMinutes, 60).'h'" icon="chart" tone="violet" />
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-5">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="card-title">Leave balance · {{ now()->year }}</h3>
                <a href="{{ route('employee.leave.create') }}" class="btn-ghost btn-sm">Apply <x-icon name="arrow-right" class="h-3.5 w-3.5" /></a>
            </div>
            <div class="space-y-4 p-5">
                @forelse ($leaveBalances ?? [] as $balance)
                    @php $pct = $balance->allocated_days > 0 ? min(100, ($balance->remaining_days / $balance->allocated_days) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-baseline justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $balance->leaveType->name }}</span>
                            <span class="tabular-nums text-slate-500"><span class="font-semibold text-slate-900">{{ rtrim(rtrim($balance->remaining_days, '0'), '.') }}</span> / {{ rtrim(rtrim($balance->allocated_days, '0'), '.') }} days</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-900/[0.07]">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-teal-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="muted">No leave balance set up yet.</p>
                @endforelse
            </div>
        </div>

        <div class="card lg:col-span-3">
            <div class="card-header">
                <h3 class="card-title">Latest announcements</h3>
                <a href="{{ route('announcements.index') }}" class="btn-ghost btn-sm">View all <x-icon name="arrow-right" class="h-3.5 w-3.5" /></a>
            </div>
            <ul class="divide-y divide-slate-900/[0.05]">
                @forelse ($announcements as $announcement)
                    <li class="flex gap-4 px-5 py-4">
                        <span @class([
                            'mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl',
                            'bg-rose-50 text-rose-600' => $announcement->priority === 'urgent',
                            'bg-amber-50 text-amber-600' => $announcement->priority === 'important',
                            'bg-slate-100 text-slate-500' => ! in_array($announcement->priority, ['urgent', 'important']),
                        ])><x-icon name="megaphone" class="h-[18px] w-[18px]" /></span>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-900">{{ $announcement->title }}</div>
                            <p class="mt-0.5 line-clamp-2 text-sm text-slate-500">{{ $announcement->description }}</p>
                            <div class="mt-1 text-xs text-slate-400">{{ $announcement->created_at->diffForHumans() }}</div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-slate-400">No announcements yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
