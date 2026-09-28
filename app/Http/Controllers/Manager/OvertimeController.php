<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Overtime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OvertimeController extends Controller
{
    public function index(Request $request): View
    {
        $departmentId = Auth::user()->employee?->department_id;

        $overtimes = Overtime::with('employee')
            ->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')), fn ($q) => $q->where('status', Overtime::STATUS_PENDING))
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString();

        return view('manager.overtime.index', compact('overtimes'));
    }

    public function approve(Overtime $overtime): RedirectResponse
    {
        $this->authorizeDepartment($overtime);

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
        $this->authorizeDepartment($overtime);

        $overtime->update([
            'status' => Overtime::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        AuditLog::record('reject', 'overtime', "Rejected OT for {$overtime->employee->full_name} ({$overtime->date->format('d M Y')})");

        return back()->with('success', 'Overtime rejected.');
    }

    private function authorizeDepartment(Overtime $overtime): void
    {
        abort_unless($overtime->employee->department_id === Auth::user()->employee?->department_id, 403);
    }
}
