<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreLeaveRequest;
use App\Models\AuditLog;
use App\Models\LeaveRequest as LeaveRequestModel;
use App\Models\LeaveType;
use App\Models\StoredFile;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403);

        $employee->ensureLeaveBalances(now()->year);

        return view('employee.leave.index', [
            'leaveRequests' => $employee->leaveRequests()->with('leaveType')->orderByDesc('start_date')->paginate(10),
            'balances' => $employee->leaveBalances()->where('year', now()->year)->with('leaveType')->get(),
        ]);
    }

    public function create(): View
    {
        $employee = Auth::user()->employee;
        $employee->ensureLeaveBalances(now()->year);

        return view('employee.leave.create', [
            'leaveTypes' => LeaveType::orderBy('name')->get(),
            'balances' => $employee->leaveBalances()->where('year', now()->year)->get()->keyBy('leave_type_id'),
        ]);
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403);

        $data = $request->validated();
        $totalDays = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;

        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $balance = $employee->leaveBalanceFor($leaveType, Carbon::parse($data['start_date'])->year);

        if ($leaveType->default_days_per_year > 0 && $balance->remaining_days < $totalDays) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'Insufficient leave balance for '.$leaveType->name.'. Remaining: '.$balance->remaining_days.' day(s).',
            ]);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = StoredFile::storeUpload($request->file('attachment'));
        }

        $leave = $employee->leaveRequests()->create([
            'leave_type_id' => $leaveType->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'total_days' => $totalDays,
            'reason' => $data['reason'],
            'attachment' => $attachmentPath,
            'status' => LeaveRequestModel::STATUS_PENDING,
        ]);

        AuditLog::record('create', 'leave', "{$employee->full_name} applied for {$leaveType->name} ({$totalDays} day(s))");

        return redirect()->route('employee.leave.index')->with('success', 'Leave request submitted.');
    }
}
