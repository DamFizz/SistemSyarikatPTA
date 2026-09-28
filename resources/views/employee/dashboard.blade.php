<x-app-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Welcome, {{ Auth::user()->name }}</h2>
        <p class="text-sm text-slate-500 mt-1">{{ $employee?->position }} &middot; {{ $employee?->department?->name }}</p>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:col-span-1">
            <div class="text-sm text-slate-500">Today's Attendance</div>
            @if ($todayAttendance)
                <div class="mt-2 text-sm space-y-1">
                    <div>Clock In: <span class="font-medium">{{ $todayAttendance->clock_in_time?->format('H:i') ?? '-' }}</span></div>
                    <div>Clock Out: <span class="font-medium">{{ $todayAttendance->clock_out_time?->format('H:i') ?? '-' }}</span></div>
                    <div class="text-xs text-emerald-600 uppercase tracking-wide">{{ str_replace('_', ' ', $todayAttendance->status) }}</div>
                </div>
            @else
                <div class="mt-2 text-sm text-slate-400">Not clocked in yet.</div>
            @endif
        </div>

        <x-stat-card label="Pending Helpdesk Tickets" :value="$pendingTickets" />

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="text-sm text-slate-500 mb-2">Leave Balance ({{ now()->year }})</div>
            @forelse ($leaveBalances ?? [] as $balance)
                <div class="flex justify-between text-sm py-0.5">
                    <span>{{ $balance->leaveType->name }}</span>
                    <span class="font-medium">{{ $balance->remaining_days }} days</span>
                </div>
            @empty
                <div class="text-sm text-slate-400">No leave balance set up yet.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-6 bg-white rounded-xl border border-slate-200 p-6 text-sm text-slate-500">
        Clock In/Out, overtime request, latest payslip, announcements and tasks will appear here as each module is built in the next phases.
    </div>
</x-app-layout>
