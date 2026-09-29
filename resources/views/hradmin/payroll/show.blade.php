<x-app-layout title="{{ $period->period_name }}">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="page-title">{{ $period->period_name }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $period->start_date->format('d M Y') }} - {{ $period->end_date->format('d M Y') }} &middot; <x-status-badge :status="$period->status" /></p>
            </div>
            <div class="flex gap-2">
                @if (in_array($period->status, ['draft', 'processing']))
                    <form method="POST" action="{{ route('hr.payroll.generate', $period) }}">
                        @csrf
                        <button class="btn-dark">Generate Payroll</button>
                    </form>
                @endif
                @if ($period->status === 'processing' && $payrolls->count() > 0)
                    <form method="POST" action="{{ route('hr.payroll.approve', $period) }}">
                        @csrf
                        <button class="btn-primary">Approve Payroll</button>
                    </form>
                @endif
                @if ($period->status === 'approved')
                    <form method="POST" action="{{ route('hr.payroll.mark-paid', $period) }}">
                        @csrf
                        <button class="btn-primary">Mark as Paid</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Basic</th>
                    <th class="px-4 py-3">Allowance</th>
                    <th class="px-4 py-3">OT</th>
                    <th class="px-4 py-3">Gross</th>
                    <th class="px-4 py-3">Deduction</th>
                    <th class="px-4 py-3">Net</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payrolls as $payroll)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $payroll->employee->full_name }}</td>
                        <td class="px-4 py-3">{{ number_format($payroll->basic_salary, 2) }}</td>
                        <td class="px-4 py-3">{{ number_format($payroll->total_allowance, 2) }}</td>
                        <td class="px-4 py-3">{{ number_format($payroll->total_ot_amount, 2) }}</td>
                        <td class="px-4 py-3">{{ number_format($payroll->gross_salary, 2) }}</td>
                        <td class="px-4 py-3">{{ number_format($payroll->total_deduction, 2) }}</td>
                        <td class="px-4 py-3 font-semibold">{{ number_format($payroll->net_salary, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($payroll->payslip)
                                <a href="{{ route('payslips.download', $payroll) }}" class="btn-secondary btn-sm">Payslip</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">No payroll generated yet. Click "Generate Payroll" to compute salaries for all active employees.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
