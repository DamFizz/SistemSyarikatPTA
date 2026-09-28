<x-app-layout title="{{ $ticket->ticket_code }}">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-semibold text-slate-800">{{ $ticket->ticket_code }} — {{ $ticket->title }}</h2>
                    <x-status-badge :status="$ticket->status" />
                    <x-status-badge :status="$ticket->priority" />
                </div>
                <p class="text-sm text-slate-500 mt-1">{{ $ticket->employee->full_name }} &middot; {{ $ticket->employee->department->name }} &middot; {{ $ticket->category->name }}</p>
            </div>
            @if (! $ticket->assigned_technician_id)
                <form method="POST" action="{{ route('technician.tickets.assign', $ticket) }}">
                    @csrf
                    <button class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Assign to Me</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-4">
        <p class="text-sm text-slate-700 whitespace-pre-line">{{ $ticket->description }}</p>
        @if ($ticket->attachment)
            <a href="{{ \Illuminate\Support\Facades\Storage::url($ticket->attachment) }}" target="_blank" class="text-emerald-600 text-sm underline mt-2 inline-block">View Attachment</a>
        @endif
    </div>

    @if ($ticket->assigned_technician_id === auth()->id() && ! in_array($ticket->status, ['resolved', 'closed']))
        <form method="POST" action="{{ route('technician.tickets.status', $ticket) }}" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 flex items-center gap-3">
            @csrf
            <label class="text-sm text-slate-600">Update Status:</label>
            <select name="status" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="in_progress" @selected($ticket->status === 'in_progress')>In Progress</option>
                <option value="waiting_user" @selected($ticket->status === 'waiting_user')>Waiting User</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-800 text-white text-sm rounded-md hover:bg-slate-700">Update</button>
        </form>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="font-semibold text-slate-700 mb-4">Timeline</h3>
        <div class="space-y-4">
            <div class="text-sm"><span class="text-slate-400">{{ $ticket->created_at->format('d M Y, H:i') }}</span> — Ticket Created</div>
            @foreach ($ticket->messages as $message)
                <div class="text-sm border-l-2 border-emerald-200 pl-3">
                    <span class="text-slate-400">{{ $message->created_at->format('d M Y, H:i') }}</span> —
                    <strong>{{ $message->sender->name }}</strong>: {{ $message->message }}
                </div>
            @endforeach
            @if ($ticket->resolved_at)
                <div class="text-sm"><span class="text-slate-400">{{ $ticket->resolved_at->format('d M Y, H:i') }}</span> — Issue Resolved</div>
            @endif
        </div>

        <form method="POST" action="{{ route('technician.tickets.reply', $ticket) }}" class="mt-6 flex gap-2">
            @csrf
            <input type="text" name="message" placeholder="Add repair notes / reply..." class="flex-1 rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" required>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Send</button>
        </form>
    </div>
</x-app-layout>
