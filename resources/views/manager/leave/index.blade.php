<x-app-layout title="Leave Approvals">
    <x-slot name="header">
        <h2 class="page-title">Leave Approvals</h2>
        <p class="text-sm text-slate-500 mt-1">Department leave requests.</p>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Dates</th>
                    <th class="px-4 py-3">Days</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leaveRequests as $leave)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $leave->employee->full_name }}</td>
                        <td class="px-4 py-3">{{ $leave->leaveType->name }}</td>
                        <td class="px-4 py-3">{{ $leave->start_date->format('d M') }} - {{ $leave->end_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $leave->total_days }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$leave->status" /></td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if ($leave->status === 'pending')
                                <form method="POST" action="{{ route('manager.leave.approve', $leave) }}" class="inline">
                                    @csrf
                                    <button class="btn-success-soft btn-sm">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('manager.leave.reject', $leave) }}" class="inline">
                                    @csrf
                                    <button class="btn-danger-soft btn-sm">Reject</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No leave requests.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $leaveRequests->links() }}</div>
</x-app-layout>
