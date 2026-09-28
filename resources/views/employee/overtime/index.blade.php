<x-app-layout title="Overtime">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-800">My Overtime</h2>
            <a href="{{ route('employee.overtime.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                + Request Overtime
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Time</th>
                    <th class="px-4 py-3">Hours</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
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
