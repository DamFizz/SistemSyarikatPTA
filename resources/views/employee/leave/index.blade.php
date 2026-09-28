<x-app-layout title="Leave">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-800">My Leave</h2>
            <a href="{{ route('employee.leave.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                + Apply Leave
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        @foreach ($balances as $balance)
            <x-stat-card :label="$balance->leaveType->name" :value="$balance->remaining_days.' days'" />
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Start</th>
                    <th class="px-4 py-3">End</th>
                    <th class="px-4 py-3">Days</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
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
