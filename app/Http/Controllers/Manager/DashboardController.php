<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee;
        $departmentId = $employee?->department_id;

        $team = $departmentId
            ? Employee::where('department_id', $departmentId)
                ->with(['attendance' => fn ($q) => $q->whereDate('attendance_date', today())])
                ->orderBy('full_name')
                ->get()
            : collect();

        return view('manager.dashboard', [
            'teamSize' => $team->count(),
            'team' => $team,
            'teamClockedIn' => $team->filter(fn ($member) => $member->attendance->first()?->clock_in_time)->count(),
            'pendingLeave' => $employee ? LeaveRequest::whereHas('employee', fn ($q) => $q->approvableBy($employee))
                ->where('status', 'pending')->count() : 0,
            'pendingOvertime' => $employee ? Overtime::whereHas('employee', fn ($q) => $q->approvableBy($employee))
                ->where('status', 'pending')->count() : 0,
        ]);
    }
}
