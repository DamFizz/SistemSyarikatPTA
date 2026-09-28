<x-app-layout title="Payroll Report">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Payroll Report</h2>
    </x-slot>

    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-slate-500 mb-1">Period</label>
            <select name="payroll_period_id" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}" @selected(request('payroll_period_id') == $period->id)>{{ $period->period_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Department</label>
            <select name="department_id" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white text-sm rounded-md hover:bg-slate-700">Filter</button>
        <a href="{{ route('hr.reports.payroll', array_merge(request()->query(), ['export' => 'csv'])) }}" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700">Export CSV</a>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3">Gross</th>
                    <th class="px-4 py-3">Deduction</th>
                    <th class="px-4 py-3">Net</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $row)
                    <tr>
                        <td class="px-4 py-3">{{ $row->employee->full_name }}</td>
                        <td class="px-4 py-3">{{ $row->employee->department->name }}</td>
                        <td class="px-4 py-3">{{ $row->payrollPeriod->period_name }}</td>
                        <td class="px-4 py-3">{{ number_format($row->gross_salary, 2) }}</td>
                        <td class="px-4 py-3">{{ number_format($row->total_deduction, 2) }}</td>
                        <td class="px-4 py-3 font-semibold">{{ number_format($row->net_salary, 2) }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$row->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
