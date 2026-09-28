<x-app-layout title="Payroll">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-800">Payroll Periods</h2>
            <a href="{{ route('hr.payroll.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                + New Payroll Period
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3">Date Range</th>
                    <th class="px-4 py-3">Employees</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($periods as $period)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $period->period_name }}</td>
                        <td class="px-4 py-3">{{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $period->payrolls_count }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$period->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('hr.payroll.show', $period) }}" class="text-emerald-600 hover:text-emerald-800 font-medium">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No payroll periods yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $periods->links() }}</div>
</x-app-layout>
