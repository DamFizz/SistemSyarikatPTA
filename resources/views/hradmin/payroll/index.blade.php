<x-app-layout title="Payroll">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="page-title">Payroll Periods</h2>
            <a href="{{ route('hr.payroll.create') }}" class="btn-primary">
                + New Payroll Period
            </a>
        </div>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3">Date Range</th>
                    <th class="px-4 py-3">Employees</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($periods as $period)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $period->period_name }}</td>
                        <td class="px-4 py-3">{{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $period->payrolls_count }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$period->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('hr.payroll.show', $period) }}" class="btn-secondary btn-sm">View</a>
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
