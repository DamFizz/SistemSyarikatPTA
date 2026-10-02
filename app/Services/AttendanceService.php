<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendancePhoto;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\PublicHoliday;
use App\Models\RestDayJustification;
use App\Support\AttendanceCapture;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(private readonly WorkHoursService $workHours) {}

    /** GPS readings less precise than this are rejected outright. */
    public const MAX_GPS_ACCURACY_METERS = 500;

    /** GPS readings less precise than this are accepted but flagged for review. */
    public const FLAG_GPS_ACCURACY_METERS = 100;

    public const MAX_SELFIE_BYTES = 3 * 1024 * 1024;

    public const MIN_SELFIE_DIMENSION = 120;

    public const STATE_NOT_CLOCKED_IN = 'not_clocked_in';

    public const STATE_CLOCKED_IN = 'clocked_in';

    public const STATE_COMPLETED = 'completed';

    public function todayRecord(Employee $employee): ?Attendance
    {
        return $employee->attendance()->whereDate('attendance_date', Carbon::today())->first();
    }

    public function state(Employee $employee): string
    {
        $today = $this->todayRecord($employee);

        return match (true) {
            ! $today || ! $today->clock_in_time => self::STATE_NOT_CLOCKED_IN,
            ! $today->clock_out_time => self::STATE_CLOCKED_IN,
            default => self::STATE_COMPLETED,
        };
    }

    /**
     * Throws unless the request comes from the office WiFi (public IP allow-list).
     */
    public function assertOnOfficeNetwork(Office $office, ?string $ip, string $field = 'attendance'): void
    {
        if (! $office->network_check_enabled) {
            return;
        }

        if (! $office->isNetworkConfigured()) {
            throw ValidationException::withMessages([
                $field => 'The office WiFi network has not been configured yet. Please contact your system administrator.',
            ]);
        }

        if (! $office->acceptsNetwork($ip)) {
            throw ValidationException::withMessages([
                $field => 'You are not connected to the office WiFi. Tap the NFC tag to connect, then try again.',
            ]);
        }
    }

    public function clockIn(Employee $employee, AttendanceCapture $capture): Attendance
    {
        if ($this->state($employee) !== self::STATE_NOT_CLOCKED_IN) {
            throw ValidationException::withMessages(['attendance' => 'You have already clocked in today.']);
        }

        $office = $employee->office;
        $this->assertOnOfficeNetwork($office, $capture->ip);
        $this->workHours->assertCanStartShift($employee);

        $restDayDue = $this->workHours->restDayRequiredToday($employee);
        $reason = trim((string) $capture->restDayReason);

        if ($restDayDue && mb_strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'rest_day_reason' => 'You have worked '.WorkHoursService::MAX_CONSECUTIVE_DAYS.' days in a row, so today is your rest day. Explain to HR why you need to work today (at least 10 characters).',
            ]);
        }

        [$distance, $locationFlags] = $this->checkLocation($office, $capture, 'Clock-in');
        $selfie = $this->validateSelfie($capture->selfieDataUrl);

        $flags = [...$locationFlags, ...$this->detectFlags($employee, $office, $capture)];

        if ($restDayDue) {
            $flags[] = 'Working without the weekly rest day (justification sent to HR)';
        }

        if ($holiday = PublicHoliday::forDate(today())) {
            $flags[] = "Worked on a public holiday ({$holiday->name})";
        }

        $shift = $employee->currentShift();

        $attendance = DB::transaction(fn () => tap(Attendance::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift?->id,
            'attendance_date' => Carbon::today(),
            'clock_in_time' => now(),
            'clock_in_lat' => $capture->latitude,
            'clock_in_lng' => $capture->longitude,
            'clock_in_distance_meters' => $distance,
            'clock_in_accuracy_meters' => $capture->accuracy !== null ? (int) round($capture->accuracy) : null,
            'selfie_path' => AttendancePhoto::STORAGE_MARKER,
            'selfie_hash' => $selfie['hash'],
            'verification_method' => $office->network_check_enabled ? 'wifi' : 'testing',
            'device_info' => Str::limit((string) $capture->userAgent, 250, ''),
            'device_hash' => $capture->deviceHash,
            'ip_address' => $capture->ip,
            'status' => $this->determineStatus(now(), $shift),
            'is_flagged' => $flags !== [],
            'flag_reasons' => $flags ?: null,
        ]), fn (Attendance $attendance) => $this->saveSelfie($attendance, 'in', $selfie)));

        if ($restDayDue) {
            RestDayJustification::create([
                'employee_id' => $employee->id,
                'attendance_id' => $attendance->id,
                'work_date' => Carbon::today(),
                'consecutive_days' => $this->workHours->consecutiveDaysBefore($employee, today()) + 1,
                'reason' => $reason,
                'status' => RestDayJustification::STATUS_PENDING,
            ]);
        }

        return $attendance;
    }

    public function clockOut(Employee $employee, AttendanceCapture $capture): array
    {
        $attendance = $employee->attendance()
            ->whereDate('attendance_date', Carbon::today())
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->first();

        if (! $attendance) {
            throw ValidationException::withMessages(['attendance' => 'No active clock-in found for today.']);
        }

        $office = $employee->office;
        $this->assertOnOfficeNetwork($office, $capture->ip);
        [$distance, $locationFlags] = $this->checkLocation($office, $capture, 'Clock-out');
        $selfie = $this->validateSelfie($capture->selfieDataUrl);

        $flags = [...$locationFlags, ...$this->detectFlags($employee, $office, $capture, $attendance)];

        $clockOutTime = now();
        $workingMinutes = max(0, (int) abs($clockOutTime->diffInMinutes($attendance->clock_in_time)));

        $shift = $attendance->shift;
        if ($shift) {
            $workingMinutes = max(0, $workingMinutes - $shift->break_duration_minutes);
        }

        $weekHours = ($this->workHours->workedMinutesInWeek($employee, $attendance->attendance_date, $attendance->id) + $workingMinutes) / 60;

        if ($weekHours > $this->workHours->weeklyHoursLimit()) {
            $flags[] = sprintf('Weekly limit exceeded (%.1fh of %gh)', $weekHours, $this->workHours->weeklyHoursLimit());
        }

        $allFlags = array_values(array_unique(array_merge($attendance->flag_reasons ?? [], $flags)));

        $attendance->update([
            'clock_out_time' => $clockOutTime,
            'clock_out_lat' => $capture->latitude,
            'clock_out_lng' => $capture->longitude,
            'clock_out_distance_meters' => $distance,
            'clock_out_accuracy_meters' => $capture->accuracy !== null ? (int) round($capture->accuracy) : null,
            'clock_out_selfie_path' => AttendancePhoto::STORAGE_MARKER,
            'clock_out_selfie_hash' => $selfie['hash'],
            'clock_out_ip_address' => $capture->ip,
            'working_minutes' => $workingMinutes,
            'is_flagged' => $allFlags !== [],
            'flag_reasons' => $allFlags ?: null,
        ]);
        $this->saveSelfie($attendance, 'out', $selfie);

        $potentialOtMinutes = 0;

        if ($shift) {
            $shiftStart = Carbon::parse($shift->start_time);
            $shiftEnd = Carbon::parse($shift->end_time);

            if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
                $shiftEnd->addDay();
            }

            $normalMinutes = (int) abs($shiftStart->diffInMinutes($shiftEnd)) - $shift->break_duration_minutes;
            $potentialOtMinutes = max(0, $workingMinutes - $normalMinutes);
        }

        $overtime = null;
        $otCapped = false;

        // Never auto-book more overtime than the employee's monthly cap allows.
        $remaining = $this->workHours->remainingOtHours($employee, $attendance->attendance_date);
        $bookableOtMinutes = $remaining === null ? $potentialOtMinutes : min($potentialOtMinutes, (int) floor($remaining * 60));

        if ($bookableOtMinutes < $potentialOtMinutes) {
            $otCapped = true;
            $attendance->update([
                'is_flagged' => true,
                'flag_reasons' => array_values(array_unique(array_merge($attendance->flag_reasons ?? [], ['Monthly overtime cap reached — extra hours not booked as OT']))),
            ]);
        }

        if ($bookableOtMinutes >= 15) {
            $overtime = Overtime::create([
                'employee_id' => $employee->id,
                'attendance_id' => $attendance->id,
                'date' => $attendance->attendance_date,
                'start_time' => $shift?->end_time ?? $attendance->clock_in_time->format('H:i:s'),
                'end_time' => $clockOutTime->format('H:i:s'),
                'total_hours' => round($bookableOtMinutes / 60, 2),
                'reason' => 'Auto-detected from attendance clock-out.',
                'status' => Overtime::STATUS_PENDING,
                'ot_rate' => 1.5,
            ]);
        }

        return [
            'attendance' => $attendance,
            'working_minutes' => $workingMinutes,
            'potential_ot_minutes' => $potentialOtMinutes,
            'overtime' => $overtime,
            'ot_capped' => $otCapped,
            'week_hours' => round($weekHours, 1),
        ];
    }

    /**
     * Location is a second factor. On the verified office WiFi the network already proves the
     * employee is on site, so a poor or missing fix (PCs and laptops have no GPS and report
     * locations kilometres off) is flagged for HR rather than blocking. In testing mode the
     * network is not checked, so the geofence is enforced.
     *
     * @return array{0: ?int, 1: list<string>}
     */
    private function checkLocation(Office $office, AttendanceCapture $capture, string $action): array
    {
        $networkVerified = $office->network_check_enabled;
        $hasFix = $capture->latitude !== null && $capture->longitude !== null;
        $distance = $hasFix ? (int) round($office->distanceTo($capture->latitude, $capture->longitude)) : null;

        if ($networkVerified) {
            return [$distance, match (true) {
                ! $hasFix => ['Location unavailable (verified by office WiFi)'],
                $distance > $office->allowed_radius_meters => ["Device location {$distance}m from office (verified by office WiFi)"],
                default => [],
            }];
        }

        if (! $hasFix) {
            throw ValidationException::withMessages([
                'attendance' => 'Your location is required. Allow location access and try again.',
            ]);
        }

        if ($capture->accuracy !== null && $capture->accuracy > self::MAX_GPS_ACCURACY_METERS) {
            throw ValidationException::withMessages([
                'attendance' => 'Your GPS signal is too weak (±'.round($capture->accuracy).'m). Turn on precise location / GPS and try again.',
            ]);
        }

        if ($distance > $office->allowed_radius_meters) {
            throw ValidationException::withMessages([
                'attendance' => "You are {$distance} meters away from your workplace. {$action} is only available within {$office->allowed_radius_meters} meters.",
            ]);
        }

        return [$distance, []];
    }

    /**
     * Soft anomalies: the action is allowed but HR sees it highlighted for review.
     *
     * @return list<string>
     */
    private function detectFlags(Employee $employee, Office $office, AttendanceCapture $capture, ?Attendance $existing = null): array
    {
        $flags = [];

        if (! $office->network_check_enabled) {
            $flags[] = 'Office WiFi check disabled (testing mode)';
        }

        if ($capture->accuracy !== null && $capture->accuracy > self::FLAG_GPS_ACCURACY_METERS) {
            $flags[] = 'Weak GPS accuracy (±'.round($capture->accuracy).'m)';
        }

        if (! $capture->deviceHash) {
            $flags[] = 'Device could not be identified';
        } elseif (! $employee->registered_device_hash) {
            $employee->forceFill([
                'registered_device_hash' => $capture->deviceHash,
                'device_registered_at' => now(),
            ])->save();
        } elseif (! hash_equals($employee->registered_device_hash, $capture->deviceHash)) {
            $flags[] = 'Unrecognised device (not the employee\'s registered phone)';
        }

        if ($capture->deviceHash) {
            $sharedDevice = Attendance::whereDate('attendance_date', Carbon::today())
                ->where('device_hash', $capture->deviceHash)
                ->where('employee_id', '!=', $employee->id)
                ->exists();

            if ($sharedDevice) {
                $flags[] = 'Same device used by another employee today (possible buddy punching)';
            }
        }

        if ($existing && $existing->device_hash && $capture->deviceHash && ! hash_equals($existing->device_hash, $capture->deviceHash)) {
            $flags[] = 'Clocked out from a different device than clock-in';
        }

        return $flags;
    }

    private function determineStatus(Carbon $clockInTime, $shift): string
    {
        if (! $shift) {
            return Attendance::STATUS_PRESENT;
        }

        $shiftStart = Carbon::parse($clockInTime->format('Y-m-d').' '.$shift->start_time);
        $graceDeadline = $shiftStart->copy()->addMinutes($shift->grace_period_minutes);

        return $clockInTime->greaterThan($graceDeadline) ? Attendance::STATUS_LATE : Attendance::STATUS_PRESENT;
    }

    /**
     * Validate that the selfie is a genuine, fresh camera image.
     *
     * @return array{binary: string, mime: string, hash: string}
     */
    private function validateSelfie(string $dataUrl): array
    {
        $invalid = fn (string $message) => ValidationException::withMessages(['selfie' => $message]);

        if (! preg_match('/^data:image\/(jpeg|png|webp);base64,/', $dataUrl)) {
            throw $invalid('Invalid selfie image. Please take a live photo with your camera.');
        }

        $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);

        if ($binary === false || strlen($binary) === 0 || strlen($binary) > self::MAX_SELFIE_BYTES) {
            throw $invalid('Selfie image is empty or too large.');
        }

        $info = @getimagesizefromstring($binary);

        if (! $info || ! in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw $invalid('Selfie is not a valid image.');
        }

        if ($info[0] < self::MIN_SELFIE_DIMENSION || $info[1] < self::MIN_SELFIE_DIMENSION) {
            throw $invalid('Selfie resolution is too low. Please allow full camera access.');
        }

        $hash = hash('sha256', $binary);

        $reused = Attendance::where('selfie_hash', $hash)->orWhere('clock_out_selfie_hash', $hash)->exists();

        if ($reused) {
            throw $invalid('This photo has already been used. Please take a new live selfie.');
        }

        return ['binary' => $binary, 'mime' => $info['mime'], 'hash' => $hash];
    }

    /**
     * Selfies are kept in the database (not the filesystem) so they survive redeploys,
     * and are only ever served through the authorised selfie route.
     *
     * @param  array{binary: string, mime: string, hash: string}  $selfie
     */
    private function saveSelfie(Attendance $attendance, string $type, array $selfie): void
    {
        $attendance->photos()->updateOrCreate(['type' => $type], [
            'mime' => $selfie['mime'],
            'size' => strlen($selfie['binary']),
            'data' => base64_encode($selfie['binary']),
        ]);
    }
}
