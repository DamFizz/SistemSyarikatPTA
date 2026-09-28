<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\Ticket;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('hradmin.dashboard', [
            'totalEmployees' => Employee::where('employment_status', 'active')->count(),
            'pendingLeave' => LeaveRequest::where('status', 'pending')->count(),
            'pendingOvertime' => Overtime::where('status', 'pending')->count(),
            'openTickets' => Ticket::whereNotIn('status', ['resolved', 'closed'])->count(),
        ]);
    }
}
