<x-app-layout title="Helpdesk Tickets">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Helpdesk Tickets — Company Wide</h2>
    </x-slot>

    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-slate-500 mb-1">Status</label>
            <select name="status" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All</option>
                @foreach (['open', 'assigned', 'in_progress', 'waiting_user', 'resolved', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') == $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white text-sm rounded-md hover:bg-slate-700">Filter</button>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Technician</th>
                    <th class="px-4 py-3">Priority</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tickets as $ticket)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $ticket->ticket_code }}</div>
                            <div class="text-xs text-slate-400">{{ $ticket->title }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $ticket->employee->full_name }} <span class="text-xs text-slate-400">({{ $ticket->employee->department->name }})</span></td>
                        <td class="px-4 py-3">{{ $ticket->technician?->name ?? '-' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$ticket->priority" /></td>
                        <td class="px-4 py-3"><x-status-badge :status="$ticket->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('hr.tickets.show', $ticket) }}" class="text-emerald-600 hover:text-emerald-800 font-medium">View</a>
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
