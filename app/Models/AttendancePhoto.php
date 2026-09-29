<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_id', 'type', 'mime', 'size', 'data'])]
#[Hidden(['data'])]
class AttendancePhoto extends Model
{
    /** Value stored in attendance.selfie_path when the photo is kept in this table. */
    public const STORAGE_MARKER = 'database';

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function binary(): string
    {
        return (string) base64_decode($this->data);
    }
}
