<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee;

        $todayAttendance = $employee?->attendance()->whereDate('attendance_date', today())->first();

        return view('employee.dashboard', [
            'employee' => $employee,
            'todayAttendance' => $todayAttendance,
            'leaveBalances' => $employee?->leaveBalances()->where('year', now()->year)->with('leaveType')->get(),
            'pendingTickets' => $employee?->tickets()->whereNotIn('status', ['resolved', 'closed'])->count() ?? 0,
        ]);
    }
}
