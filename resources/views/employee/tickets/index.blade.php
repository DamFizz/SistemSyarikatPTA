<x-app-layout title="Helpdesk">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-800">My Helpdesk Tickets</h2>
            <a href="{{ route('employee.tickets.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                + Submit Ticket
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Priority</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
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
                        <td class="px-4 py-3">{{ $ticket->category->name }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$ticket->priority" /></td>
                        <td class="px-4 py-3"><x-status-badge :status="$ticket->status" /></td>
                        <td class="px-4 py-3">{{ $ticket->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('employee.tickets.show', $ticket) }}" class="text-emerald-600 hover:text-emerald-800 font-medium">View</a>
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
