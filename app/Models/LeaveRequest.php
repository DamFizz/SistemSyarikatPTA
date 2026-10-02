<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        DB::transaction(function () use ($approverId) {
            $balance = $this->employee->leaveBalanceFor($this->leaveType, $this->start_date->year);
            $balance = LeaveBalance::whereKey($balance->id)->lockForUpdate()->first();

            // Several pending requests can together exceed the balance — check again at approval.
            if ($this->leaveType->default_days_per_year > 0 && $balance->remaining_days < $this->total_days) {
                throw ValidationException::withMessages([
                    'request' => "{$this->employee->full_name} only has {$balance->remaining_days} day(s) of {$this->leaveType->name} left, so this {$this->total_days}-day request cannot be approved.",
                ]);
            }

            $this->update([
                'status' => self::STATUS_APPROVED,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            $balance->increment('used_days', $this->total_days);
            $balance->decrement('remaining_days', $this->total_days);
        });
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
