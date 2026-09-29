<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Company-wide announcements plus those for the user's own department.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $departmentId = $user->employee?->department_id;

        return $query->where(fn (Builder $q) => $q->whereNull('department_id')->orWhere('department_id', $departmentId));
    }

    /**
     * Announcements posted since the user last opened (or dismissed) the announcements.
     */
    public function scopeUnseenBy(Builder $query, User $user): Builder
    {
        return $query->visibleTo($user)
            ->where('created_by', '!=', $user->id)
            ->when($user->announcements_seen_at, fn (Builder $q, $seenAt) => $q->where('created_at', '>', $seenAt));
    }
}
