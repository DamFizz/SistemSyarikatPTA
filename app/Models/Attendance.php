<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Table('attendance')]
#[Fillable([
    'employee_id', 'shift_id', 'attendance_date', 'clock_in_time', 'clock_out_time',
    'clock_in_lat', 'clock_in_lng', 'clock_in_distance_meters', 'clock_in_accuracy_meters',
    'clock_out_lat', 'clock_out_lng', 'clock_out_distance_meters', 'clock_out_accuracy_meters',
    'selfie_path', 'selfie_hash', 'clock_out_selfie_path', 'clock_out_selfie_hash',
    'qr_token_id', 'verification_method', 'device_info', 'device_hash', 'ip_address', 'clock_out_ip_address',
    'working_minutes', 'status', 'is_flagged', 'flag_reasons',
])]
class Attendance extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_LATE = 'late';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_HALF_DAY = 'half_day';

    public const STATUS_ON_LEAVE = 'on_leave';

    public const STATUS_PUBLIC_HOLIDAY = 'public_holiday';

    public const STATUS_WORK_FROM_HOME = 'work_from_home';

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'clock_in_time' => 'datetime',
            'clock_out_time' => 'datetime',
            'clock_in_lat' => 'decimal:7',
            'clock_in_lng' => 'decimal:7',
            'clock_out_lat' => 'decimal:7',
            'clock_out_lng' => 'decimal:7',
            'is_flagged' => 'boolean',
            'flag_reasons' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function qrToken(): BelongsTo
    {
        return $this->belongsTo(AttendanceQrToken::class, 'qr_token_id');
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(AttendancePhoto::class);
    }

    public function selfiePath(string $type): ?string
    {
        return $type === 'out' ? $this->clock_out_selfie_path : $this->selfie_path;
    }

    /**
     * Whether the clock-in / clock-out selfie can still be shown
     * (it may have been purged, or lost from disk for older records).
     */
    public function hasSelfie(string $type): bool
    {
        $path = $this->selfiePath($type);

        if (! $path) {
            return false;
        }

        if ($path === AttendancePhoto::STORAGE_MARKER) {
            return $this->relationLoaded('photos')
                ? $this->photos->contains('type', $type)
                : $this->photos()->where('type', $type)->exists();
        }

        return Storage::disk('local')->exists($path) || Storage::disk('public')->exists($path);
    }
}
