<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceQrToken;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function clockIn(Employee $employee, float $lat, float $lng, string $selfieDataUrl, ?string $ip, ?string $deviceInfo, ?string $qrToken = null, ?string $nfcTagId = null): Attendance
    {
        $today = Carbon::today();

        if ($employee->attendance()->whereDate('attendance_date', $today)->whereNotNull('clock_in_time')->exists()) {
            throw ValidationException::withMessages(['clock_in' => 'You have already clocked in today.']);
        }

        $office = $employee->office;
        $distance = (int) round($office->distanceTo($lat, $lng));

        if ($distance > $office->allowed_radius_meters) {
            throw ValidationException::withMessages([
                'clock_in' => "You are {$distance} meters away from your workplace. Clock-in is only available within {$office->allowed_radius_meters} meters.",
            ]);
        }

        [$verificationMethod, $qrTokenId] = $this->verifyCheckpoint($office, $qrToken, $nfcTagId);

        $selfiePath = $this->storeSelfie($selfieDataUrl, $employee->id);

        $shift = $employee->currentShift();
        $status = $this->determineStatus(now(), $shift);

        return Attendance::create([
            'employee_id' => $employee->id,
            'shift_id' => $shift?->id,
            'attendance_date' => $today,
            'clock_in_time' => now(),
            'clock_in_lat' => $lat,
            'clock_in_lng' => $lng,
            'clock_in_distance_meters' => $distance,
            'selfie_path' => $selfiePath,
            'qr_token_id' => $qrTokenId,
            'verification_method' => $verificationMethod,
            'device_info' => $deviceInfo,
            'ip_address' => $ip,
            'status' => $status,
        ]);
    }

    /**
     * Verify the checkpoint via QR token or NFC tag. Returns [method, qr_token_id|null].
     *
     * @return array{0: string, 1: int|null}
     */
    private function verifyCheckpoint(Office $office, ?string $qrToken, ?string $nfcTagId): array
    {
        if ($nfcTagId) {
            if (! $office->nfc_tag_id || ! hash_equals($office->nfc_tag_id, $nfcTagId)) {
                throw ValidationException::withMessages(['clock_in' => 'NFC tag not recognized for this office. Please use QR instead.']);
            }

            return ['nfc', null];
        }

        if ($qrToken) {
            $token = AttendanceQrToken::where('token', $qrToken)
                ->where('office_id', $office->id)
                ->where('expires_at', '>', now())
                ->first();

            if (! $token) {
                throw ValidationException::withMessages(['clock_in' => 'QR code expired or invalid. Please scan the current attendance QR.']);
            }

            return ['qr', $token->id];
        }

        throw ValidationException::withMessages(['clock_in' => 'Checkpoint verification (QR or NFC) is required.']);
    }

    public function clockOut(Employee $employee, float $lat, float $lng): array
    {
        $attendance = $employee->attendance()
            ->whereDate('attendance_date', Carbon::today())
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->first();

        if (! $attendance) {
            throw ValidationException::withMessages(['clock_out' => 'No active clock-in found for today.']);
        }

        $office = $employee->office;
        $distance = (int) round($office->distanceTo($lat, $lng));

        if ($distance > $office->allowed_radius_meters) {
            throw ValidationException::withMessages([
                'clock_out' => "You are {$distance} meters away from your workplace. Clock-out is only available within {$office->allowed_radius_meters} meters.",
            ]);
        }

        $clockOutTime = now();
        $workingMinutes = max(0, (int) abs($clockOutTime->diffInMinutes($attendance->clock_in_time)));

        $shift = $attendance->shift;
        if ($shift) {
            $workingMinutes = max(0, $workingMinutes - $shift->break_duration_minutes);
        }

        $attendance->update([
            'clock_out_time' => $clockOutTime,
            'clock_out_lat' => $lat,
            'clock_out_lng' => $lng,
            'working_minutes' => $workingMinutes,
        ]);

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

        if ($potentialOtMinutes >= 15) {
            $overtime = Overtime::create([
                'employee_id' => $employee->id,
                'attendance_id' => $attendance->id,
                'date' => $attendance->attendance_date,
                'start_time' => $shift?->end_time ?? $attendance->clock_in_time->format('H:i:s'),
                'end_time' => $clockOutTime->format('H:i:s'),
                'total_hours' => round($potentialOtMinutes / 60, 2),
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
        ];
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

    private function storeSelfie(string $dataUrl, int $employeeId): string
    {
        if (! preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) {
            throw ValidationException::withMessages(['clock_in' => 'Invalid selfie image.']);
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $content = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1));

        $path = "selfies/{$employeeId}_" . now()->format('YmdHis') . ".{$extension}";
        Storage::disk('public')->put($path, $content);

        return $path;
    }
}
