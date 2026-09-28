<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function index(): View
    {
        $employee = Auth::user()->employee;

        abort_if(! $employee, 403, 'Only staff with an employee profile can access attendance.');

        $today = $employee->attendance()->whereDate('attendance_date', today())->first();
        $history = $employee->attendance()->orderByDesc('attendance_date')->limit(14)->get();

        return view('employee.attendance.index', [
            'employee' => $employee,
            'office' => $employee->office,
            'today' => $today,
            'history' => $history,
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'selfie' => ['required', 'string'],
            'qr_token' => ['required_without:nfc_tag_id', 'nullable', 'string'],
            'nfc_tag_id' => ['required_without:qr_token', 'nullable', 'string'],
        ]);

        $employee = Auth::user()->employee;
        abort_if(! $employee, 403);

        $attendance = $this->attendanceService->clockIn(
            $employee,
            (float) $data['latitude'],
            (float) $data['longitude'],
            $data['selfie'],
            $request->ip(),
            $request->userAgent(),
            $data['qr_token'] ?? null,
            $data['nfc_tag_id'] ?? null,
        );

        AuditLog::record('clock_in', 'attendance', "{$employee->full_name} clocked in ({$attendance->status})");

        return redirect()->route('employee.attendance.index')->with('success', 'Clock-in successful. Status: '.str($attendance->status)->replace('_', ' ')->title());
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $employee = Auth::user()->employee;
        abort_if(! $employee, 403);

        $result = $this->attendanceService->clockOut($employee, (float) $data['latitude'], (float) $data['longitude']);

        AuditLog::record('clock_out', 'attendance', "{$employee->full_name} clocked out");

        $hours = intdiv($result['working_minutes'], 60);
        $minutes = $result['working_minutes'] % 60;
        $message = "Clock-out successful. Working hours: {$hours}h {$minutes}m.";

        if ($result['potential_ot_minutes'] > 0) {
            $otHours = intdiv($result['potential_ot_minutes'], 60);
            $otMinutes = $result['potential_ot_minutes'] % 60;
            $message .= " Potential overtime detected: {$otHours}h {$otMinutes}m (pending approval).";
        }

        return redirect()->route('employee.attendance.index')->with('success', $message);
    }
}
