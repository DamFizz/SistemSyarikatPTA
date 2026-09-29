<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * Employment Act working-hour rules: monthly overtime cap, weekly hours limit and the weekly rest day.
 * Limits are configurable by HR (stored in settings).
 */
class WorkHoursService
{
    public const DEFAULT_OT_MONTHLY_CAP = 104;

    public const DEFAULT_OT_SALARY_THRESHOLD = 4000;

    public const DEFAULT_WEEKLY_HOURS_LIMIT = 60;

    /** Working this many days in a row means today must be a rest day. */
    public const MAX_CONSECUTIVE_DAYS = 6;

    public const CAP_MODES = [
        'auto' => 'Auto (by salary)',
        'enforced' => 'Always capped',
        'exempt' => 'Exempt',
    ];

    public function otMonthlyCap(): float
    {
        return (float) Setting::get('ot_monthly_cap_hours', self::DEFAULT_OT_MONTHLY_CAP);
    }

    public function otSalaryThreshold(): float
    {
        return (float) Setting::get('ot_cap_salary_threshold', self::DEFAULT_OT_SALARY_THRESHOLD);
    }

    public function weeklyHoursLimit(): float
    {
        return (float) Setting::get('weekly_hours_limit', self::DEFAULT_WEEKLY_HOURS_LIMIT);
    }

    public function restDayEnforced(): bool
    {
        return (bool) Setting::get('rest_day_enforced', true);
    }

    // ------------------------------------------------------------------
    // Monthly overtime cap
    // ------------------------------------------------------------------

    public function otCapApplies(Employee $employee): bool
    {
        return match ($employee->ot_cap_mode ?? 'auto') {
            'enforced' => true,
            'exempt' => false,
            // Unknown salary is treated as covered — the safe side of the law.
            default => (float) ($employee->currentSalary()?->basic_salary ?? 0) <= $this->otSalaryThreshold(),
        };
    }

    /**
     * Overtime hours already booked in the month of $date.
     *
     * @param  list<string>  $statuses
     */
    public function otHoursInMonth(Employee $employee, CarbonInterface $date, array $statuses, ?int $ignoreId = null): float
    {
        return (float) $employee->overtimes()
            ->whereDate('date', '>=', $date->copy()->startOfMonth()->toDateString())
            ->whereDate('date', '<=', $date->copy()->endOfMonth()->toDateString())
            ->whereIn('status', $statuses)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->sum('total_hours');
    }

    /**
     * Hours still available this month, or null when the cap doesn't apply.
     */
    public function remainingOtHours(Employee $employee, CarbonInterface $date): ?float
    {
        if (! $this->otCapApplies($employee)) {
            return null;
        }

        $used = $this->otHoursInMonth($employee, $date, [Overtime::STATUS_PENDING, Overtime::STATUS_APPROVED, Overtime::STATUS_PAID]);

        return max(0, round($this->otMonthlyCap() - $used, 2));
    }

    /**
     * New requests count everything already pending or approved.
     */
    public function assertOtRequestWithinCap(Employee $employee, CarbonInterface $date, float $hours): void
    {
        $remaining = $this->remainingOtHours($employee, $date);

        if ($remaining !== null && $hours > $remaining + 0.001) {
            throw ValidationException::withMessages([
                'end_time' => sprintf(
                    'This request (%sh) exceeds your monthly overtime limit. You have %sh left of the %sh allowed for %s.',
                    $this->fmt($hours), $this->fmt($remaining), $this->fmt($this->otMonthlyCap()), $date->format('F Y')
                ),
            ]);
        }
    }

    /**
     * Approval only counts overtime that is already approved or paid.
     */
    public function assertOtApprovalWithinCap(Overtime $overtime): void
    {
        $employee = $overtime->employee;

        if (! $this->otCapApplies($employee)) {
            return;
        }

        $approved = $this->otHoursInMonth($employee, $overtime->date, [Overtime::STATUS_APPROVED, Overtime::STATUS_PAID], $overtime->id);

        if ($approved + (float) $overtime->total_hours > $this->otMonthlyCap() + 0.001) {
            throw ValidationException::withMessages([
                'overtime' => sprintf(
                    'Approving this would give %s %sh of overtime in %s — above the %sh monthly limit.',
                    $employee->full_name, $this->fmt($approved + (float) $overtime->total_hours),
                    $overtime->date->format('F Y'), $this->fmt($this->otMonthlyCap())
                ),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Weekly hours limit
    // ------------------------------------------------------------------

    public function weekStart(CarbonInterface $date): Carbon
    {
        return Carbon::parse($date)->startOfWeek(CarbonInterface::MONDAY);
    }

    /**
     * Minutes worked in the Monday–Sunday week of $date, including a shift still in progress.
     */
    public function workedMinutesInWeek(Employee $employee, CarbonInterface $date, ?int $excludeAttendanceId = null): int
    {
        $start = $this->weekStart($date);

        $records = $employee->attendance()
            ->whereDate('attendance_date', '>=', $start->toDateString())
            ->whereDate('attendance_date', '<=', $start->copy()->endOfWeek(CarbonInterface::SUNDAY)->toDateString())
            ->whereNotNull('clock_in_time')
            ->when($excludeAttendanceId, fn ($q) => $q->whereKeyNot($excludeAttendanceId))
            ->get();

        return (int) $records->sum(function (Attendance $record) {
            if ($record->clock_out_time) {
                return (int) $record->working_minutes;
            }

            return $record->attendance_date->isToday() ? (int) abs(now()->diffInMinutes($record->clock_in_time)) : 0;
        });
    }

    public function workedHoursInWeek(Employee $employee, CarbonInterface $date): float
    {
        return round($this->workedMinutesInWeek($employee, $date) / 60, 1);
    }

    public function assertCanStartShift(Employee $employee): void
    {
        $worked = $this->workedHoursInWeek($employee, now());

        if ($worked >= $this->weeklyHoursLimit()) {
            throw ValidationException::withMessages([
                'attendance' => sprintf(
                    'You have already worked %sh this week. The legal limit is %sh per week, so you cannot clock in again until next Monday.',
                    $this->fmt($worked), $this->fmt($this->weeklyHoursLimit())
                ),
            ]);
        }
    }

    /**
     * Overtime on a day with no attendance record adds to the week; otherwise it is already counted.
     */
    public function assertOtWithinWeeklyLimit(Employee $employee, CarbonInterface $date, float $hours): void
    {
        $worked = $this->workedMinutesInWeek($employee, $date) / 60;
        $dayRecorded = $employee->attendance()->whereDate('attendance_date', $date)->whereNotNull('clock_in_time')->exists();
        $projected = $worked + ($dayRecorded ? 0 : $hours);

        if ($projected > $this->weeklyHoursLimit() + 0.001) {
            throw ValidationException::withMessages([
                'end_time' => sprintf(
                    'This would bring the week of %s to %sh of work, above the %sh weekly limit.',
                    $this->weekStart($date)->format('d M'), $this->fmt($projected), $this->fmt($this->weeklyHoursLimit())
                ),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Weekly rest day
    // ------------------------------------------------------------------

    /**
     * Days worked in a row immediately before $date (not counting $date itself).
     */
    public function consecutiveDaysBefore(Employee $employee, CarbonInterface $date): int
    {
        $worked = $employee->attendance()
            ->whereNotNull('clock_in_time')
            ->whereDate('attendance_date', '>=', $date->copy()->subDays(14)->toDateString())
            ->whereDate('attendance_date', '<', $date->toDateString())
            ->pluck('attendance_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->flip();

        $count = 0;
        $day = Carbon::parse($date)->subDay();

        while ($worked->has($day->toDateString())) {
            $count++;
            $day->subDay();
        }

        return $count;
    }

    /**
     * True when clocking in today would mean working without the weekly rest day.
     */
    public function restDayRequiredToday(Employee $employee): bool
    {
        return $this->restDayEnforced() && $this->consecutiveDaysBefore($employee, today()) >= self::MAX_CONSECUTIVE_DAYS;
    }

    /**
     * Snapshot for dashboards / the attendance page.
     *
     * @return array<string, mixed>
     */
    public function summary(Employee $employee): array
    {
        $capApplies = $this->otCapApplies($employee);
        $otUsed = $this->otHoursInMonth($employee, now(), [Overtime::STATUS_PENDING, Overtime::STATUS_APPROVED, Overtime::STATUS_PAID]);
        $consecutive = $this->consecutiveDaysBefore($employee, today());
        $workedToday = $employee->attendance()->whereDate('attendance_date', today())->whereNotNull('clock_in_time')->exists();

        return [
            'week_hours' => $this->workedHoursInWeek($employee, now()),
            'week_limit' => $this->weeklyHoursLimit(),
            'ot_cap_applies' => $capApplies,
            'ot_used' => round($otUsed, 2),
            'ot_cap' => $this->otMonthlyCap(),
            'consecutive_days' => $consecutive + ($workedToday ? 1 : 0),
            'rest_day_required' => ! $workedToday && $this->restDayRequiredToday($employee),
        ];
    }

    private function fmt(float $hours): string
    {
        return rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.');
    }
}
