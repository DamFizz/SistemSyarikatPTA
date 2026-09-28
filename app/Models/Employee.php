<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'employee_code', 'full_name', 'ic_number', 'phone', 'gender', 'dob',
    'address', 'profile_photo', 'department_id', 'office_id', 'manager_id', 'position',
    'employment_type', 'employment_status', 'join_date',
])]
class Employee extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'join_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    public function currentShift(): ?Shift
    {
        return $this->shifts()
            ->whereDate('effective_date', '<=', now())
            ->orderByDesc('effective_date')
            ->with('shift')
            ->first()?->shift;
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function currentSalary(): ?EmployeeSalary
    {
        return $this->salaries()->orderByDesc('effective_date')->first();
    }

    /**
     * Hourly rate derived from basic salary using the standard 26 working days / 8 hours divisor.
     */
    public function hourlyRate(): float
    {
        $basicSalary = (float) ($this->currentSalary()?->basic_salary ?? 0);

        return round($basicSalary / 26 / 8, 2);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
