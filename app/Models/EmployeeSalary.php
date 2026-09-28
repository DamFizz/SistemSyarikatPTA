<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'basic_salary', 'allowance', 'epf_rate', 'socso_rate', 'eis_rate', 'effective_date'])]
class EmployeeSalary extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowance' => 'decimal:2',
            'epf_rate' => 'decimal:2',
            'socso_rate' => 'decimal:2',
            'eis_rate' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
