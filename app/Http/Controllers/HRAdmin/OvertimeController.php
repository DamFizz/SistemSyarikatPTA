<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Overtime;
use App\Services\WorkHoursService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OvertimeController extends Controller
{
    public function index(Request $request): View
    {
        $overtimes = Overtime::with(['employee.department', 'approver'])
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString();

        return view('hradmin.overtime.index', [
            'overtimes' => $overtimes,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function approve(Overtime $overtime, WorkHoursService $workHours): RedirectResponse
    {
        $this->authorizeFallbackApproval($overtime->employee, $overtime->status);
        $workHours->assertOtApprovalWithinCap($overtime);

        $overtime->update([
            'status' => Overtime::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'amount' => round($overtime->employee->hourlyRate() * $overtime->ot_rate * $overtime->total_hours, 2),
        ]);

        AuditLog::record('approve', 'overtime', "Approved OT for {$overtime->employee->full_name} ({$overtime->date->format('d M Y')})");

        return back()->with('success', 'Overtime approved.');
    }

    public function reject(Overtime $overtime): RedirectResponse
    {
        $this->authorizeFallbackApproval($overtime->employee, $overtime->status);

        $overtime->update([
            'status' => Overtime::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        AuditLog::record('reject', 'overtime', "Rejected OT for {$overtime->employee->full_name} ({$overtime->date->format('d M Y')})");

        return back()->with('success', 'Overtime rejected.');
    }

    /**
     * HR Admin never approves. Super Admin may, but only when no manager can approve the employee.
     */
    private function authorizeFallbackApproval(Employee $employee, string $status): void
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403, 'Overtime is approved by the employee\'s manager.');
        if (! ($status === Overtime::STATUS_PENDING)) {
            throw ValidationException::withMessages(['request' => 'This request has already been processed.']);
        }
        abort_if($employee->approvingManager() !== null, 403, 'This employee has a manager who must approve the request.');
    }
}
