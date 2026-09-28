<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; color: #059669; }
        .header p { margin: 2px 0; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        td, th { padding: 6px 4px; text-align: left; }
        .info-table td { border-bottom: 1px solid #e2e8f0; }
        .items-table th { background: #f1f5f9; border-bottom: 2px solid #cbd5e1; }
        .items-table td { border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #1e293b; }
        .net-salary { background: #ecfdf5; padding: 12px; border-radius: 6px; margin-top: 12px; }
        .net-salary strong { color: #059669; font-size: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p>Payslip — {{ $payroll->payrollPeriod->period_name }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Employee Name</strong></td>
            <td>{{ $payroll->employee->full_name }}</td>
            <td><strong>Employee Code</strong></td>
            <td>{{ $payroll->employee->employee_code }}</td>
        </tr>
        <tr>
            <td><strong>Department</strong></td>
            <td>{{ $payroll->employee->department->name }}</td>
            <td><strong>Position</strong></td>
            <td>{{ $payroll->employee->position }}</td>
        </tr>
        <tr>
            <td><strong>Payment Date</strong></td>
            <td>{{ $payroll->payslip?->generated_at?->format('d M Y') }}</td>
            <td><strong>Period</strong></td>
            <td>{{ $payroll->payrollPeriod->start_date->format('d M Y') }} - {{ $payroll->payrollPeriod->end_date->format('d M Y') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Earnings</th>
                <th class="text-right">Amount (RM)</th>
                <th>Deductions</th>
                <th class="text-right">Amount (RM)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Salary</td>
                <td class="text-right">{{ number_format($payroll->basic_salary, 2) }}</td>
                <td>EPF</td>
                <td class="text-right">{{ number_format($payroll->items->firstWhere('type', 'epf')?->amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Allowance</td>
                <td class="text-right">{{ number_format($payroll->total_allowance, 2) }}</td>
                <td>SOCSO</td>
                <td class="text-right">{{ number_format($payroll->items->firstWhere('type', 'socso')?->amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Overtime</td>
                <td class="text-right">{{ number_format($payroll->total_ot_amount, 2) }}</td>
                <td>EIS</td>
                <td class="text-right">{{ number_format($payroll->items->firstWhere('type', 'eis')?->amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Bonus</td>
                <td class="text-right">{{ number_format($payroll->total_bonus, 2) }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td>Gross Salary</td>
                <td class="text-right">{{ number_format($payroll->gross_salary, 2) }}</td>
                <td>Total Deduction</td>
                <td class="text-right">{{ number_format($payroll->total_deduction, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="net-salary">
        Net Salary: <strong>RM {{ number_format($payroll->net_salary, 2) }}</strong>
    </div>
</body>
</html>
