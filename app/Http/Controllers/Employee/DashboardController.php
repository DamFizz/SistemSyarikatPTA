<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee;

        $todayAttendance = $employee?->attendance()->whereDate('attendance_date', today())->first();

        $monthRecords = $employee?->attendance()
            ->whereBetween('attendance_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();

        return view('employee.dashboard', [
            'employee' => $employee,
            'todayAttendance' => $todayAttendance,
            'leaveBalances' => $employee?->leaveBalances()->where('year', now()->year)->with('leaveType')->get(),
            'pendingTickets' => $employee?->tickets()->whereNotIn('status', ['resolved', 'closed'])->count() ?? 0,
            'monthPresent' => $monthRecords?->whereNotNull('clock_in_time')->count() ?? 0,
            'monthLate' => $monthRecords?->where('status', Attendance::STATUS_LATE)->count() ?? 0,
            'monthMinutes' => (int) ($monthRecords?->sum('working_minutes') ?? 0),
            'announcements' => Announcement::where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $employee?->department_id))
                ->latest()
                ->limit(3)
                ->get(),
        ]);
    }
}
