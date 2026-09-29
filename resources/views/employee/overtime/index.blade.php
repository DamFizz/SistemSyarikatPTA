<x-app-layout title="Overtime">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="page-title">My Overtime</h2>
            <a href="{{ route('employee.overtime.create') }}" class="btn-primary">
                + Request Overtime
            </a>
        </div>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Time</th>
                    <th class="px-4 py-3">Hours</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($overtimes as $ot)
                    <tr>
                        <td class="px-4 py-3">{{ $ot->date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $ot->start_time }} - {{ $ot->end_time }}</td>
                        <td class="px-4 py-3">{{ $ot->total_hours }}h</td>
                        <td class="px-4 py-3 max-w-xs truncate">{{ $ot->reason }}</td>
                        <td class="px-4 py-3">{{ $ot->amount ? 'RM '.number_format($ot->amount, 2) : '-' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$ot->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No overtime records yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $overtimes->links() }}</div>
</x-app-layout>
