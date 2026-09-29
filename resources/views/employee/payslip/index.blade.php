<x-app-layout title="My Payslips">
    <x-slot name="header">
        <h2 class="page-title">My Payslips</h2>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3">Gross Salary</th>
                    <th class="px-4 py-3">Deduction</th>
                    <th class="px-4 py-3">Net Salary</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payrolls as $payroll)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $payroll->payrollPeriod->period_name }}</td>
                        <td class="px-4 py-3">RM {{ number_format($payroll->gross_salary, 2) }}</td>
                        <td class="px-4 py-3">RM {{ number_format($payroll->total_deduction, 2) }}</td>
                        <td class="px-4 py-3 font-semibold">RM {{ number_format($payroll->net_salary, 2) }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$payroll->status" /></td>
                        <td class="px-4 py-3 text-right">
                            @if ($payroll->payslip)
                                <a href="{{ route('payslips.download', $payroll) }}" class="btn-secondary btn-sm">Download PDF</a>
                            @else
                                <span class="text-slate-400 text-xs">Not issued yet</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No payslips yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payrolls->links() }}</div>
</x-app-layout>
