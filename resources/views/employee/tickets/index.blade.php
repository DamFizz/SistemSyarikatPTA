<x-app-layout title="Helpdesk">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="page-title">My Helpdesk Tickets</h2>
            <a href="{{ route('employee.tickets.create') }}" class="btn-primary">
                + Submit Ticket
            </a>
        </div>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Priority</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
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
                        <td class="px-4 py-3">{{ $ticket->category->name }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$ticket->priority" /></td>
                        <td class="px-4 py-3"><x-status-badge :status="$ticket->status" /></td>
                        <td class="px-4 py-3">{{ $ticket->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('employee.tickets.show', $ticket) }}" class="btn-secondary btn-sm">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No tickets yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tickets->links() }}</div>
</x-app-layout>
