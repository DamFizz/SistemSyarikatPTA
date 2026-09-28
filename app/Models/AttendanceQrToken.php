<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['office_id', 'checkpoint_name', 'token', 'expires_at', 'is_used'])]
#[WithoutTimestamps]
class AttendanceQrToken extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_used' => 'boolean',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'qr_token_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
