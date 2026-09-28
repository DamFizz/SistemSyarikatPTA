<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'attendance_id', 'date', 'start_time', 'end_time', 'total_hours',
    'reason', 'attachment', 'status', 'approved_by', 'approved_at', 'ot_rate', 'amount',
])]
class Overtime extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PAID = 'paid';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'approved_at' => 'datetime',
            'total_hours' => 'decimal:2',
            'ot_rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
