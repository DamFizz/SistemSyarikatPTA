<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'leave_type_id', 'year', 'allocated_days', 'used_days', 'remaining_days'])]
class LeaveBalance extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'allocated_days' => 'decimal:1',
            'used_days' => 'decimal:1',
            'remaining_days' => 'decimal:1',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
