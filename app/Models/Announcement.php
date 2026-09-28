<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'description', 'category', 'priority', 'created_by', 'department_id', 'attachment'])]
class Announcement extends Model
{
    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_IMPORTANT = 'important';

    public const PRIORITY_URGENT = 'urgent';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
