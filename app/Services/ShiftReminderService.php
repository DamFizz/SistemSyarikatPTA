<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;

/**
 * Works out today's clock-in deadline for an employee, so the UI can show a
 * countdown. Employees who are not expected at work today get state "off".
 */
class ShiftReminderService
{
    /** Countdown warning starts this many seconds before the shift. */
    public const WARNING_SECONDS = 600;

    /** Full-screen overlay for the final seconds. */
    public const FINAL_SECONDS = 10;

    /** Cookie remembering which account last signed in on this device. */
    public const DEVICE_COOKIE = 'sems_shift_device';

    public function __construct(private readonly WorkHoursService $workHours) {}

    public function forUser(?User $user): ?array
    {
        return $user?->employee ? $this->forEmployee($user->employee) : null;
    }

    /**
     * @return array{name: string, state: string, off_reason: ?string, shift_name: ?string, start: ?string, start_label: ?string, clocked_in_at: ?string, server_now: int, warning_seconds: int, final_seconds: int}
     */
    public function forEmployee(Employee $employee): array
    {
        $base = [
            'name' => (string) str($employee->full_name)->before(' '),
            'state' => 'off',
            'off_reason' => null,
            'shift_name' => null,
            'start' => null,
            'start_label' => null,
            'end' => null,
            'end_label' => null,
            'clocked_in_at' => null,
            'server_now' => (int) round(microtime(true) * 1000),
            'warning_seconds' => self::WARNING_SECONDS,
            'final_seconds' => self::FINAL_SECONDS,
        ];

        $today = $employee->attendance()->whereDate('attendance_date', today())->first();

        if ($today?->clock_in_time) {
            return [...$base, 'state' => 'clocked_in', 'clocked_in_at' => $today->clock_in_time->format('h:i A')];
        }

        $offReason = $this->offReason($employee, $today);

        if ($offReason) {
            return [...$base, 'off_reason' => $offReason];
        }

        $shift = $employee->currentShift();
        $start = Carbon::parse(today()->toDateString().' '.$shift->start_time);
        $end = Carbon::parse(today()->toDateString().' '.$shift->end_time);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay(); // overnight shift
        }

        return [
            ...$base,
            'state' => 'upcoming',
            'shift_name' => $shift->name,
            'start' => $start->toIso8601String(),
            'start_label' => $start->format('h:i A'),
            'end' => $end->toIso8601String(),
            'end_label' => $end->format('h:i A'),
        ];
    }

    private function offReason(Employee $employee, ?Attendance $today): ?string
    {
        if (! in_array($employee->employment_status, ['active', 'probation'], true)) {
            return 'inactive';
        }

        if ($today && in_array($today->status, [Attendance::STATUS_ON_LEAVE, Attendance::STATUS_PUBLIC_HOLIDAY, Attendance::STATUS_ABSENT], true)) {
            return $today->status === Attendance::STATUS_PUBLIC_HOLIDAY ? 'holiday' : 'leave';
        }

        $onLeave = $employee->leaveRequests()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->exists();

        if ($onLeave) {
            return 'leave';
        }

        if ($this->workHours->restDayRequiredToday($employee)) {
            return 'rest_day';
        }

        if (! $employee->currentShift()) {
            return 'no_shift';
        }

        return null;
    }
}
