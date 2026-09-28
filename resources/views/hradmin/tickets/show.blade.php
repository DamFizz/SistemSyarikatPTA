<x-app-layout title="{{ $ticket->ticket_code }}">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-semibold text-slate-800">{{ $ticket->ticket_code }} — {{ $ticket->title }}</h2>
            <x-status-badge :status="$ticket->status" />
            <x-status-badge :status="$ticket->priority" />
        </div>
        <p class="text-sm text-slate-500 mt-1">{{ $ticket->employee->full_name }} ({{ $ticket->employee->department->name }}) &middot; Technician: {{ $ticket->technician?->name ?? 'Not yet assigned' }}</p>
    </x-slot>

    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-4">
        <p class="text-sm text-slate-700 whitespace-pre-line">{{ $ticket->description }}</p>
    </div>

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
    </div>
</x-app-layout>
