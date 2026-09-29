<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Overtime;
use App\Services\WorkHoursService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OvertimeController extends Controller
{
    public function __construct(private readonly WorkHoursService $workHours) {}

    public function index(Request $request): View
    {
        $manager = $this->manager();

        $overtimes = Overtime::with('employee.department')
            ->whereHas('employee', fn ($q) => $q->approvableBy($manager))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', Overtime::STATUS_PENDING))
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString();

        return view('manager.overtime.index', [
            'overtimes' => $overtimes,
            'workHours' => $this->workHours,
        ]);
    }

    public function approve(Overtime $overtime): RedirectResponse
    {
        $this->authorizeApprover($overtime);
        $this->workHours->assertOtApprovalWithinCap($overtime);

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
        $this->authorizeApprover($overtime);

        $overtime->update([
            'status' => Overtime::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        AuditLog::record('reject', 'overtime', "Rejected OT for {$overtime->employee->full_name} ({$overtime->date->format('d M Y')})");

        return back()->with('success', 'Overtime rejected.');
    }

    private function manager(): Employee
    {
        $manager = Auth::user()->employee;
        abort_if(! $manager, 403, 'Your account has no employee profile.');

        return $manager;
    }

    private function authorizeApprover(Overtime $overtime): void
    {
        abort_unless($overtime->status === Overtime::STATUS_PENDING, 422, 'This request has already been processed.');
        abort_unless($overtime->employee->isApprovableBy($this->manager()), 403, 'You are not the approving manager for this employee.');
    }
}
