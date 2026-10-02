<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeShift;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Shift, salary and leave entitlements set from the employee forms. Shift and salary are
 * kept as dated history, so a change applies from the given date and payroll / attendance
 * for earlier periods keep using the old values.
 */
class EmployeeRecordsService
{
    public function assignShift(Employee $employee, ?int $shiftId, CarbonInterface $effective): void
    {
        if (! $shiftId || $employee->currentShift()?->id === $shiftId) {
            return;
        }

        $this->datedRecord(EmployeeShift::class, $employee, $effective)->fill(['shift_id' => $shiftId])->save();
    }

    public function setSalary(Employee $employee, mixed $basic, mixed $allowance, CarbonInterface $effective): void
    {
        if ($basic === null || $basic === '') {
            return;
        }

        $current = $employee->currentSalary();
        $allowance = (float) ($allowance ?: 0);

        if ($current && (float) $current->basic_salary === (float) $basic && (float) $current->allowance === $allowance) {
            return;
        }

        $this->datedRecord(EmployeeSalary::class, $employee, $effective)->fill([
            'basic_salary' => $basic,
            'allowance' => $allowance,
            'epf_rate' => $current->epf_rate ?? 11.00,
            'socso_rate' => $current->socso_rate ?? 0.50,
            'eis_rate' => $current->eis_rate ?? 0.20,
        ])->save();
    }

    public function createLeaveBalances(Employee $employee): void
    {
        $employee->ensureLeaveBalances(now()->year);
    }

    /**
     * The record effective on that exact date (edited in place), or a new one.
     *
     * @template T of Model
     *
     * @param  class-string<T>  $model
     * @return T
     */
    private function datedRecord(string $model, Employee $employee, CarbonInterface $effective): Model
    {
        return $model::where('employee_id', $employee->id)->whereDate('effective_date', $effective)->first()
            ?? new $model(['employee_id' => $employee->id, 'effective_date' => $effective->toDateString()]);
    }
}
