<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\PublicHoliday;
use App\Services\AttendanceService;
use App\Services\SelfieRetentionService;
use App\Services\WorkHoursService;
use App\Support\AttendanceCapture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public const DEVICE_COOKIE = 'sems_device';

    public const CHALLENGE_KEY = 'attendance_challenge';

    /** How long the employee has between "Clock In/Out" and submitting the selfie. */
    public const CHALLENGE_TTL_SECONDS = 180;

    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly WorkHoursService $workHours,
    ) {}

    public function index(Request $request, SelfieRetentionService $retention): View
    {
        $employee = $this->employee();
        $retention->purgeIfDue();

        if (! $request->cookie(self::DEVICE_COOKIE)) {
            Cookie::queue(Cookie::forever(self::DEVICE_COOKIE, Str::random(48), null, null, null, true));
        }

        return view('employee.attendance.index', [
            'employee' => $employee,
            'office' => $employee->office,
            'today' => $this->attendanceService->todayRecord($employee),
            'state' => $this->attendanceService->state($employee),
            'history' => $employee->attendance()->with('photos:id,attendance_id,type')->orderByDesc('attendance_date')->limit(14)->get(),
            'workSummary' => $this->workHours->summary($employee),
            'holiday' => PublicHoliday::forDate(today()),
        ]);
    }

    /**
     * Polled by the attendance page to detect when the phone joins the office WiFi.
     */
    public function status(Request $request): JsonResponse
    {
        $employee = $this->employee();
        $office = $employee->office;

        return response()->json([
            'state' => $this->attendanceService->state($employee),
            'on_office_network' => $office->acceptsNetwork($request->ip()),
            'network_check_enabled' => $office->network_check_enabled,
            'network_configured' => $office->isNetworkConfigured(),
            'ssid' => $office->wifi_ssid,
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Opens a short-lived, single-use verification session bound to this IP and action.
     */
    public function begin(Request $request): JsonResponse
    {
        $data = $request->validate(['action' => ['required', 'in:in,out']]);
        $employee = $this->employee();

        $expectedState = $data['action'] === 'in' ? AttendanceService::STATE_NOT_CLOCKED_IN : AttendanceService::STATE_CLOCKED_IN;

        if ($this->attendanceService->state($employee) !== $expectedState) {
            throw ValidationException::withMessages(['attendance' => 'Your attendance status has changed. Please refresh the page.']);
        }

        $this->attendanceService->assertOnOfficeNetwork($employee->office, $request->ip());

        if ($data['action'] === 'in') {
            $this->workHours->assertCanStartShift($employee);
        }

        $nonce = Str::random(64);

        $request->session()->put(self::CHALLENGE_KEY, [
            'nonce_hash' => hash('sha256', $nonce),
            'action' => $data['action'],
            'ip' => $request->ip(),
            'expires_at' => now()->addSeconds(self::CHALLENGE_TTL_SECONDS)->getTimestamp(),
        ]);

        return response()->json(['challenge' => $nonce, 'expires_in' => self::CHALLENGE_TTL_SECONDS]);
    }

    public function clockIn(Request $request): JsonResponse|RedirectResponse
    {
        $employee = $this->employee();
        $capture = $this->capture($request, 'in');

        $attendance = $this->attendanceService->clockIn($employee, $capture);

        AuditLog::record('clock_in', 'attendance', "{$employee->full_name} clocked in ({$attendance->status})".($attendance->is_flagged ? ' [FLAGGED]' : ''));

        $message = 'Clock-in successful at '.$attendance->clock_in_time->format('h:i A').'. Status: '.str($attendance->status)->replace('_', ' ')->title().'.';

        return $this->respond($request, $message);
    }

    public function clockOut(Request $request): JsonResponse|RedirectResponse
    {
        $employee = $this->employee();
        $capture = $this->capture($request, 'out');

        $result = $this->attendanceService->clockOut($employee, $capture);

        AuditLog::record('clock_out', 'attendance', "{$employee->full_name} clocked out".($result['attendance']->is_flagged ? ' [FLAGGED]' : ''));

        $hours = intdiv($result['working_minutes'], 60);
        $minutes = $result['working_minutes'] % 60;
        $message = "Clock-out successful. Working hours: {$hours}h {$minutes}m.";

        if ($result['potential_ot_minutes'] > 0) {
            $otHours = intdiv($result['potential_ot_minutes'], 60);
            $otMinutes = $result['potential_ot_minutes'] % 60;
            $message .= " Potential overtime detected: {$otHours}h {$otMinutes}m (pending approval).";
        }

        if ($result['ot_capped']) {
            $message .= ' Your monthly overtime limit has been reached, so extra hours were not booked as overtime.';
        }

        if ($result['week_hours'] > $this->workHours->weeklyHoursLimit()) {
            $message .= " Warning: you have worked {$result['week_hours']}h this week, above the legal weekly limit.";
        }

        return $this->respond($request, $message);
    }

    private function capture(Request $request, string $action): AttendanceCapture
    {
        $data = $request->validate([
            'challenge' => ['required', 'string', 'size:64'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'selfie' => ['required', 'string', 'max:'.(int) (AttendanceService::MAX_SELFIE_BYTES * 1.4)],
            'rest_day_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->consumeChallenge($request, $data['challenge'], $action);

        $deviceToken = $request->cookie(self::DEVICE_COOKIE);

        return new AttendanceCapture(
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            accuracy: isset($data['accuracy']) ? (float) $data['accuracy'] : null,
            selfieDataUrl: $data['selfie'],
            ip: $request->ip(),
            userAgent: $request->userAgent(),
            deviceHash: is_string($deviceToken) && $deviceToken !== '' ? hash('sha256', $deviceToken) : null,
            restDayReason: $data['rest_day_reason'] ?? null,
        );
    }

    /**
     * The challenge is single-use, expires quickly and must come from the same network
     * that passed the office WiFi check, so requests cannot be replayed or forged later.
     */
    private function consumeChallenge(Request $request, string $nonce, string $action): void
    {
        $challenge = $request->session()->pull(self::CHALLENGE_KEY);

        $valid = is_array($challenge)
            && hash_equals($challenge['nonce_hash'], hash('sha256', $nonce))
            && $challenge['action'] === $action
            && $challenge['ip'] === $request->ip()
            && $challenge['expires_at'] >= now()->getTimestamp();

        if (! $valid) {
            throw ValidationException::withMessages([
                'attendance' => 'Your verification session expired or is invalid. Please start again.',
            ]);
        }
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            session()->flash('success', $message);

            return response()->json(['message' => $message, 'redirect' => route('employee.attendance.index')]);
        }

        return redirect()->route('employee.attendance.index')->with('success', $message);
    }

    private function employee(): Employee
    {
        $employee = Auth::user()->employee;

        abort_if(! $employee, 403, 'Only staff with an employee profile can access attendance.');
        abort_if(! $employee->office, 403, 'You are not assigned to an office. Please contact HR.');

        return $employee;
    }
}
