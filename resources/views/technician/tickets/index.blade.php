<x-app-layout title="Ticket Queue">
    <x-slot name="header">
        <h2 class="page-title">Helpdesk Ticket Queue</h2>
    </x-slot>

    <form method="GET" class="filter-bar">
        <div>
            <label class="block text-xs text-slate-500 mb-1">Status</label>
            <select name="status" class="input">
                <option value="">Open (default)</option>
                @foreach (['open', 'assigned', 'in_progress', 'waiting_user', 'resolved', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') == $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="mine" value="1" @checked(request('mine')) >
            My tickets only
        </label>
        <button type="submit" class="btn-dark">Filter</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Priority</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $ticket->ticket_code }}</div>
                            <div class="text-xs text-slate-400">{{ $ticket->title }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $ticket->employee->full_name }}</td>
                        <td class="px-4 py-3">{{ $ticket->category->name }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$ticket->priority" /></td>
                        <td class="px-4 py-3"><x-status-badge :status="$ticket->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('technician.tickets.show', $ticket) }}" class="btn-secondary btn-sm">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No tickets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tickets->links() }}</div>
</x-app-layout>
