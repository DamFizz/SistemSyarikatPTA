<x-app-layout title="Dashboard">
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">{{ now()->format('l, d F Y') }}</p>
                <h2 class="page-title mt-1">Team Overview</h2>
                <p class="muted mt-1">Welcome back, {{ Auth::user()->name }}.</p>
            </div>
            <a href="{{ route('manager.employees.create') }}" class="btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Add team member</a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Team size" :value="$teamSize" icon="users" :href="route('manager.employees.index')" />
        <x-stat-card label="In today" :value="$teamClockedIn.' / '.$teamSize" icon="fingerprint" tone="sky" />
        <x-stat-card label="Leave to approve" :value="$pendingLeave" icon="calendar" tone="amber" :href="route('manager.leave.index')" />
        <x-stat-card label="Overtime to approve" :value="$pendingOvertime" icon="clock" tone="violet" :href="route('manager.overtime.index')" />
    </div>

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">Who's in today</h3>
            <span class="chip bg-slate-100 text-slate-600">{{ $teamClockedIn }} of {{ $teamSize }} clocked in</span>
        </div>
        <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($team as $member)
                @php $record = $member->attendance->first(); @endphp
                <div class="flex items-center gap-3 glass-inset p-3">
                    <div class="relative">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/80 text-sm font-bold text-slate-700 ring-1 ring-white shadow-sm">{{ strtoupper(substr($member->full_name, 0, 1)) }}</div>
                        <span @class([
                            'absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full ring-2 ring-white',
                            'bg-emerald-500' => $record?->clock_in_time && ! $record?->clock_out_time,
                            'bg-slate-400' => $record?->clock_out_time,
                            'bg-slate-200' => ! $record?->clock_in_time,
                        ])></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-slate-900">{{ $member->full_name }}</div>
                        <div class="truncate text-xs text-slate-500">
                            @if ($record?->clock_out_time)
                                Left at {{ $record->clock_out_time->format('H:i') }}
                            @elseif ($record?->clock_in_time)
                                In since {{ $record->clock_in_time->format('H:i') }}
                            @else
                                Not clocked in
                            @endif
                        </div>
                    </div>
                    @if ($record)
                        <x-status-badge :status="$record->status" />
                    @endif
                </div>
            @empty
                <p class="muted col-span-full py-6 text-center">No team members in your department yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
