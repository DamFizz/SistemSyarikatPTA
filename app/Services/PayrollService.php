<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\PayrollPeriod;

class PayrollService
{
    public function generate(PayrollPeriod $period): int
    {
        $existingEmployeeIds = $period->payrolls()->pluck('employee_id');

        // Staff on probation are paid too; only those who have left (or are suspended) are skipped.
        $employees = Employee::whereIn('employment_status', ['active', 'probation'])
            ->whereNotIn('id', $existingEmployeeIds)
            ->get();

        foreach ($employees as $employee) {
            $this->generateForEmployee($period, $employee);
        }

        if ($period->status === PayrollPeriod::STATUS_DRAFT) {
            $period->update(['status' => PayrollPeriod::STATUS_PROCESSING]);
        }

        return $employees->count();
    }

    private function generateForEmployee(PayrollPeriod $period, Employee $employee): Payroll
    {
        $salary = $employee->currentSalary();
        $basicSalary = (float) ($salary->basic_salary ?? 0);
        $allowance = (float) ($salary->allowance ?? 0);

        $approvedOt = Overtime::where('employee_id', $employee->id)
            ->where('status', Overtime::STATUS_APPROVED)
            ->whereBetween('date', [$period->start_date, $period->end_date])
            ->sum('amount');

        $grossSalary = $basicSalary + $allowance + $approvedOt;

        $epf = round($grossSalary * (float) ($salary->epf_rate ?? 11) / 100, 2);
        $socso = round($grossSalary * (float) ($salary->socso_rate ?? 0.5) / 100, 2);
        $eis = round($grossSalary * (float) ($salary->eis_rate ?? 0.2) / 100, 2);
        $totalDeduction = $epf + $socso + $eis;
        $netSalary = $grossSalary - $totalDeduction;

        $payroll = Payroll::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'basic_salary' => $basicSalary,
            'total_allowance' => $allowance,
            'total_bonus' => 0,
            'total_ot_amount' => $approvedOt,
            'gross_salary' => $grossSalary,
            'total_deduction' => $totalDeduction,
            'net_salary' => $netSalary,
            'status' => PayrollPeriod::STATUS_PROCESSING,
        ]);

        if ($allowance > 0) {
            $payroll->items()->create(['type' => 'allowance', 'label' => 'Fixed Allowance', 'amount' => $allowance]);
        }
        if ($approvedOt > 0) {
            $payroll->items()->create(['type' => 'ot', 'label' => 'Approved Overtime', 'amount' => $approvedOt]);
        }
        $payroll->items()->create(['type' => 'epf', 'label' => 'EPF', 'amount' => $epf]);
        $payroll->items()->create(['type' => 'socso', 'label' => 'SOCSO', 'amount' => $socso]);
        $payroll->items()->create(['type' => 'eis', 'label' => 'EIS', 'amount' => $eis]);

        // Mark the overtime records as paid once included in a payroll run.
        Overtime::where('employee_id', $employee->id)
            ->where('status', Overtime::STATUS_APPROVED)
            ->whereBetween('date', [$period->start_date, $period->end_date])
            ->update(['status' => Overtime::STATUS_PAID]);

        return $payroll;
    }
}
