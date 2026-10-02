<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $manager = $this->manager();

        $leaveRequests = LeaveRequest::with(['employee.department', 'leaveType'])
            ->whereHas('employee', fn ($q) => $q->approvableBy($manager))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', LeaveRequest::STATUS_PENDING))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        return view('manager.leave.index', compact('leaveRequests'));
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorizeApprover($leaveRequest);

        $leaveRequest->approveAndDeductBalance(Auth::id());

        AuditLog::record('approve', 'leave', "Approved leave for {$leaveRequest->employee->full_name}");

        return back()->with('success', 'Leave approved.');
    }

    public function reject(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorizeApprover($leaveRequest);

        $leaveRequest->rejectRequest(Auth::id());

        AuditLog::record('reject', 'leave', "Rejected leave for {$leaveRequest->employee->full_name}");

        return back()->with('success', 'Leave rejected.');
    }

    private function manager(): Employee
    {
        $manager = Auth::user()->employee;
        abort_if(! $manager, 403, 'Your account has no employee profile.');

        return $manager;
    }

    private function authorizeApprover(LeaveRequest $leaveRequest): void
    {
        if (! ($leaveRequest->status === LeaveRequest::STATUS_PENDING)) {
            throw ValidationException::withMessages(['request' => 'This request has already been processed.']);
        }
        abort_unless($leaveRequest->employee->isApprovableBy($this->manager()), 403, 'You are not the approving manager for this employee.');
    }
}
