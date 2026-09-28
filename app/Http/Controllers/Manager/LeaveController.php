<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $departmentId = Auth::user()->employee?->department_id;

        $leaveRequests = LeaveRequest::with(['employee', 'leaveType'])
            ->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', LeaveRequest::STATUS_PENDING))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        return view('manager.leave.index', compact('leaveRequests'));
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorizeDepartment($leaveRequest);
        $leaveRequest->approveAndDeductBalance(Auth::id());

        AuditLog::record('approve', 'leave', "Approved leave for {$leaveRequest->employee->full_name}");

        return back()->with('success', 'Leave approved.');
    }

    public function reject(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorizeDepartment($leaveRequest);
        $leaveRequest->rejectRequest(Auth::id());

        AuditLog::record('reject', 'leave', "Rejected leave for {$leaveRequest->employee->full_name}");

        return back()->with('success', 'Leave rejected.');
    }

    private function authorizeDepartment(LeaveRequest $leaveRequest): void
    {
        abort_unless($leaveRequest->employee->department_id === Auth::user()->employee?->department_id, 403);
    }
}
