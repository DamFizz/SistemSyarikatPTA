<x-app-layout title="Overtime Approvals">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Overtime Approvals</h2>
        <p class="text-sm text-slate-500 mt-1">Department overtime requests.</p>
    </x-slot>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Time</th>
                    <th class="px-4 py-3">Hours</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($overtimes as $ot)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $ot->employee->full_name }}</td>
                        <td class="px-4 py-3">{{ $ot->date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $ot->start_time }} - {{ $ot->end_time }}</td>
                        <td class="px-4 py-3">{{ $ot->total_hours }}h</td>
                        <td class="px-4 py-3 max-w-xs truncate">{{ $ot->reason }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$ot->status" /></td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if ($ot->status === 'pending')
                                <form method="POST" action="{{ route('manager.overtime.approve', $ot) }}" class="inline">
                                    @csrf
                                    <button class="text-emerald-600 hover:text-emerald-800 font-medium">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('manager.overtime.reject', $ot) }}" class="inline">
                                    @csrf
                                    <button class="text-red-600 hover:text-red-800 font-medium">Reject</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">No overtime requests.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $overtimes->links() }}</div>
</x-app-layout>
