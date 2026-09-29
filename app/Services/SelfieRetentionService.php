<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendancePhoto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Attendance selfies are only kept for the month they were taken in (plus any
 * configured extra months). Attendance records and selfie hashes are kept, so
 * duplicate-photo detection keeps working after the pictures are gone.
 */
class SelfieRetentionService
{
    public function cutoff(): Carbon
    {
        return now()->startOfMonth()->subMonths(max(0, (int) config('attendance.selfie_retention_months', 0)));
    }

    /**
     * @return int number of selfies deleted
     */
    public function purge(): int
    {
        $deleted = 0;

        Attendance::whereDate('attendance_date', '<', $this->cutoff()->toDateString())
            ->where(fn ($q) => $q->whereNotNull('selfie_path')->orWhereNotNull('clock_out_selfie_path'))
            ->chunkById(200, function ($records) use (&$deleted) {
                foreach ($records as $attendance) {
                    foreach (['selfie_path', 'clock_out_selfie_path'] as $column) {
                        $path = $attendance->{$column};

                        if ($path && $path !== AttendancePhoto::STORAGE_MARKER) {
                            // Older records stored on disk.
                            Storage::disk('local')->delete($path);
                            Storage::disk('public')->delete($path);
                        }

                        if ($path) {
                            $deleted++;
                        }
                    }

                    $attendance->photos()->delete();
                    $attendance->forceFill(['selfie_path' => null, 'clock_out_selfie_path' => null])->save();
                }
            });

        return $deleted;
    }

    /**
     * Runs the purge at most once a day, piggy-backing on normal traffic so it
     * works even where no scheduler / cron is running.
     */
    public function purgeIfDue(): void
    {
        if (Cache::add('selfie-retention:'.today()->toDateString(), true, now()->endOfDay())) {
            $this->purge();
        }
    }
}
