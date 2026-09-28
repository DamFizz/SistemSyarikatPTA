<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ticket_code', 'employee_id', 'department_id', 'category_id', 'title', 'description',
    'priority', 'attachment', 'assigned_technician_id', 'status', 'resolved_at',
])]
class Ticket extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_WAITING_USER = 'waiting_user';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }
}
