<x-app-layout title="Overtime">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Overtime — Company Wide</h2>
    </x-slot>

    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-slate-500 mb-1">Department</label>
            <select name="department_id" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Status</label>
            <select name="status" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All</option>
                @foreach (['pending', 'approved', 'rejected', 'paid'] as $status)
                    <option value="{{ $status }}" @selected(request('status') == $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white text-sm rounded-md hover:bg-slate-700">Filter</button>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Hours</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($overtimes as $ot)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $ot->employee->full_name }}</td>
                        <td class="px-4 py-3">{{ $ot->employee->department->name }}</td>
                        <td class="px-4 py-3">{{ $ot->date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $ot->total_hours }}h</td>
                        <td class="px-4 py-3">{{ $ot->amount ? 'RM '.number_format($ot->amount, 2) : '-' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$ot->status" /></td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if ($ot->status === 'pending')
                                <form method="POST" action="{{ route('hr.overtime.approve', $ot) }}" class="inline">
                                    @csrf
                                    <button class="text-emerald-600 hover:text-emerald-800 font-medium">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('hr.overtime.reject', $ot) }}" class="inline">
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
