<x-app-layout title="Helpdesk Report">
    <x-slot name="header">
        <h2 class="page-title">Helpdesk Report</h2>
    </x-slot>

    <form method="GET" class="filter-bar">
        <div>
            <label class="block text-xs text-slate-500 mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="input">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="input">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Status</label>
            <select name="status" class="input">
                <option value="">All</option>
                @foreach (['open', 'assigned', 'in_progress', 'waiting_user', 'resolved', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') == $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-dark">Filter</button>
        <a href="{{ route('hr.reports.helpdesk', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn-primary">Export CSV</a>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Priority</th>
                    <th class="px-4 py-3">Technician</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3">{{ $row->ticket_code }}</td>
                        <td class="px-4 py-3">{{ $row->employee->full_name }}</td>
                        <td class="px-4 py-3">{{ $row->category->name }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$row->priority" /></td>
                        <td class="px-4 py-3">{{ $row->technician?->name ?? '-' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$row->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
