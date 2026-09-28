<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'payroll_period_id', 'employee_id', 'basic_salary', 'total_allowance', 'total_bonus',
    'total_ot_amount', 'gross_salary', 'total_deduction', 'net_salary', 'status',
])]
class Payroll extends Model
{
    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'total_allowance' => 'decimal:2',
            'total_bonus' => 'decimal:2',
            'total_ot_amount' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'net_salary' => 'decimal:2',
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }
}
