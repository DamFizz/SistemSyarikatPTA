<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
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

        return view('manager.dashboard', [
            'teamSize' => $departmentId ? \App\Models\Employee::where('department_id', $departmentId)->count() : 0,
            'pendingLeave' => LeaveRequest::whereHas('employee', fn ($q) => $q->where('department_id', $departmentId))
                ->where('status', 'pending')->count(),
            'pendingOvertime' => Overtime::whereHas('employee', fn ($q) => $q->where('department_id', $departmentId))
                ->where('status', 'pending')->count(),
        ]);
    }
}
