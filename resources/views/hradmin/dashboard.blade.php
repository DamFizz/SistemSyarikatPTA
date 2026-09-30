<x-app-layout title="Dashboard">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">{{ now()->format('l, d F Y') }}</p>
                <h2 class="page-title mt-1">HR Overview</h2>
                <p class="muted mt-1">Welcome back, {{ Auth::user()->name }}. Here's what needs your attention today.</p>
            </div>
            <a href="{{ route('hr.employees.create') }}" class="btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Add employee</a>
        </div>
    </x-slot>

    @if ($pendingJustifications)
        <a href="{{ route('hr.work-hours.index') }}" class="alert-warning mb-5 !text-amber-800 hover:bg-amber-100/70">
            <x-icon name="calendar" class="h-5 w-5 shrink-0" />
            <span class="flex-1"><strong>{{ $pendingJustifications }}</strong> employee(s) worked through their weekly rest day and submitted a justification for review.</span>
            <x-icon name="arrow-right" class="h-4 w-4 shrink-0" />
        </a>
    @endif

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Active employees" :value="$totalEmployees" icon="users" :href="route('hr.employees.index')" />
        <x-stat-card label="Pending leave" :value="$pendingLeave" icon="calendar" tone="amber" :href="route('hr.leave.index')" />
        <x-stat-card label="Pending overtime" :value="$pendingOvertime" icon="clock" tone="violet" :href="route('hr.overtime.index')" />
        <x-stat-card label="Open tickets" :value="$openTickets" icon="lifebuoy" tone="sky" :href="route('hr.tickets.index')" />
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        {{-- Today's attendance ring --}}
        @php
            $rate = $totalEmployees > 0 ? round(($todayPresent / $totalEmployees) * 100) : 0;
            $circumference = 2 * pi() * 52;
        @endphp
        <div class="surface-dark p-6">
            <div class="relative">
                <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Attendance today</div>
                <div class="mt-5 flex items-center gap-6">
                    <div class="relative h-32 w-32 shrink-0">
                        <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="10" />
                            <circle cx="60" cy="60" r="52" fill="none" stroke="url(#attendance-ring)" stroke-width="10" stroke-linecap="round"
                                    stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $circumference * (1 - $rate / 100) }}" />
                            <defs><linearGradient id="attendance-ring"><stop offset="0" stop-color="#6ee7b7" /><stop offset="1" stop-color="#14b8a6" /></linearGradient></defs>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-bold text-white">{{ $rate }}%</span>
                            <span class="text-[11px] text-slate-400">present</span>
                        </div>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-slate-500">Clocked in</dt><dd class="text-lg font-semibold text-white">{{ $todayPresent }} <span class="text-sm font-normal text-slate-500">/ {{ $totalEmployees }}</span></dd></div>
                        <div><dt class="text-slate-500">Late</dt><dd class="text-lg font-semibold text-amber-300">{{ $todayLate }}</dd></div>
                        <div><dt class="text-slate-500">Flagged</dt><dd class="text-lg font-semibold text-rose-300">{{ $todayFlagged }}</dd></div>
                    </dl>
                </div>
                <a href="{{ route('hr.attendance.index') }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-300 hover:text-emerald-200">Open attendance records <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </div>
        </div>

        {{-- Flagged attendance --}}
        <div class="card lg:col-span-2">
            <div class="card-header">
                <div class="flex items-center gap-2">
                    <x-icon name="flag" class="h-[18px] w-[18px] text-rose-500" />
                    <h3 class="card-title">Flagged for review · last 7 days</h3>
                </div>
                <a href="{{ route('hr.attendance.index', ['flagged' => 1]) }}" class="btn-ghost btn-sm">Review</a>
            </div>
            <ul class="divide-y divide-slate-900/[0.05]">
                @forelse ($flaggedRecords as $record)
                    <li class="flex items-start gap-3 px-5 py-3.5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-sm font-bold text-rose-600">{{ strtoupper(substr($record->employee->full_name, 0, 1)) }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 text-sm">
                                <span class="font-semibold text-slate-900">{{ $record->employee->full_name }}</span>
                                <span class="text-xs text-slate-400">{{ $record->attendance_date->format('d M') }} · {{ $record->clock_in_time?->format('H:i') }}</span>
                            </div>
                            <div class="mt-0.5 truncate text-xs text-rose-600">{{ implode(' · ', $record->flag_reasons ?? ['Flagged']) }}</div>
                        </div>
                    </li>
                @empty
                    <li class="flex flex-col items-center gap-2 px-5 py-10 text-center text-sm text-slate-400">
                        <x-icon name="shield" class="h-8 w-8 text-emerald-400" />
                        No suspicious attendance this week.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="card mt-5 overflow-x-auto">
        <div class="card-header">
            <h3 class="card-title">Leave requests awaiting approval</h3>
            <a href="{{ route('hr.leave.index') }}" class="btn-ghost btn-sm">View all <x-icon name="arrow-right" class="h-3.5 w-3.5" /></a>
        </div>
        <table class="table-modern">
            <thead>
                <tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Submitted</th></tr>
            </thead>
            <tbody>
                @forelse ($recentLeave as $leave)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $leave->employee->full_name }}</td>
                        <td class="px-4 py-3">{{ $leave->leaveType->name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $leave->start_date->format('d M') }} – {{ $leave->end_date->format('d M') }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $leave->total_days }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $leave->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">All caught up — no pending leave.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
