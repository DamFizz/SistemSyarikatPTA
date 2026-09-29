<x-app-layout title="Overtime Records">
    <x-slot name="header">
        <p class="eyebrow">Operations</p>
        <h2 class="page-title mt-1">Overtime Records</h2>
        <p class="muted mt-1">Company-wide record. Requests are approved by each employee's manager.</p>
    </x-slot>

    <form method="GET" class="filter-bar">
        <div>
            <label class="block text-xs text-slate-500 mb-1">Department</label>
            <select name="department_id" class="input">
                <option value="">All</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Status</label>
            <select name="status" class="input">
                <option value="">All</option>
                @foreach (['pending', 'approved', 'rejected', 'paid'] as $status)
                    <option value="{{ $status }}" @selected(request('status') == $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-dark">Filter</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Hours</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Approval</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($overtimes as $ot)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $ot->employee->full_name }}</td>
                        <td class="px-4 py-3">{{ $ot->employee->department->name }}</td>
                        <td class="px-4 py-3">{{ $ot->date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $ot->total_hours }}h</td>
                        <td class="px-4 py-3">{{ $ot->amount ? 'RM '.number_format($ot->amount, 2) : '-' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$ot->status" /></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-1.5">
                            <x-approval-cell :request="$ot" approve-route="hr.overtime.approve" reject-route="hr.overtime.reject" />
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
