<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id', 'leave_type_id', 'start_date', 'end_date', 'total_days',
    'reason', 'attachment', 'status', 'approved_by', 'approved_at',
])]
class LeaveRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
            'total_days' => 'decimal:1',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approveAndDeductBalance(int $approverId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);

        $balance = $this->employee->leaveBalances()
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $this->start_date->year)
            ->first();

        if ($balance) {
            $balance->increment('used_days', $this->total_days);
            $balance->decrement('remaining_days', $this->total_days);
        }
    }

    public function rejectRequest(int $approverId): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
    }
}
