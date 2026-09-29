<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\Ticket;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeEmployees = Employee::whereIn('employment_status', ['active', 'probation'])->count();
        $today = Attendance::whereDate('attendance_date', today())->whereNotNull('clock_in_time');

        return view('hradmin.dashboard', [
            'totalEmployees' => $activeEmployees,
            'pendingLeave' => LeaveRequest::where('status', 'pending')->count(),
            'pendingOvertime' => Overtime::where('status', 'pending')->count(),
            'openTickets' => Ticket::whereNotIn('status', ['resolved', 'closed'])->count(),
            'todayPresent' => (clone $today)->count(),
            'todayLate' => (clone $today)->where('status', Attendance::STATUS_LATE)->count(),
            'todayFlagged' => (clone $today)->where('is_flagged', true)->count(),
            'flaggedRecords' => Attendance::with('employee')->where('is_flagged', true)
                ->where('attendance_date', '>=', today()->subDays(7))
                ->latest('clock_in_time')
                ->limit(5)
                ->get(),
            'recentLeave' => LeaveRequest::with(['employee', 'leaveType'])->where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
