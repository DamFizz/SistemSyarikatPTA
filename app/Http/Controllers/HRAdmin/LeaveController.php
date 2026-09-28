<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $leaveRequests = LeaveRequest::with(['employee.department', 'leaveType'])
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        return view('hradmin.leave.index', [
            'leaveRequests' => $leaveRequests,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        $leaveRequest->approveAndDeductBalance(Auth::id());

        AuditLog::record('approve', 'leave', "Approved leave for {$leaveRequest->employee->full_name}");

        return back()->with('success', 'Leave approved.');
    }

    public function reject(LeaveRequest $leaveRequest): RedirectResponse
    {
        $leaveRequest->rejectRequest(Auth::id());

        AuditLog::record('reject', 'leave', "Rejected leave for {$leaveRequest->employee->full_name}");

        return back()->with('success', 'Leave rejected.');
    }
}
