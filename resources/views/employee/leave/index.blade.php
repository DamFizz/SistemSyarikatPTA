<x-app-layout title="Leave">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="page-title">My Leave</h2>
            <a href="{{ route('employee.leave.create') }}" class="btn-primary">
                + Apply Leave
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        @foreach ($balances as $balance)
            <x-stat-card :label="$balance->leaveType->name" :value="$balance->remaining_days.' days'" />
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Start</th>
                    <th class="px-4 py-3">End</th>
                    <th class="px-4 py-3">Days</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leaveRequests as $leave)
                    <tr>
                        <td class="px-4 py-3">{{ $leave->leaveType->name }}</td>
                        <td class="px-4 py-3">{{ $leave->start_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $leave->end_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $leave->total_days }}</td>
                        <td class="px-4 py-3 max-w-xs truncate">{{ $leave->reason }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$leave->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No leave requests yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $leaveRequests->links() }}</div>
</x-app-layout>
