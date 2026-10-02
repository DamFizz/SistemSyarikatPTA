<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreOvertimeRequest;
use App\Models\AuditLog;
use App\Models\Overtime;
use App\Models\StoredFile;
use App\Services\WorkHoursService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OvertimeController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403);

        $overtimes = $employee->overtimes()->orderByDesc('date')->paginate(10);

        return view('employee.overtime.index', compact('overtimes'));
    }

    public function create(WorkHoursService $workHours): View
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403);

        return view('employee.overtime.create', ['workSummary' => $workHours->summary($employee)]);
    }

    public function store(StoreOvertimeRequest $request, WorkHoursService $workHours): RedirectResponse
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403);

        $data = $request->validated();

        $start = Carbon::parse($data['date'].' '.$data['start_time']);
        $end = Carbon::parse($data['date'].' '.$data['end_time']);
        $totalHours = round(abs($end->diffInMinutes($start)) / 60, 2);

        // Employment Act limits: monthly overtime cap and the weekly hours limit.
        $workHours->assertOtRequestWithinCap($employee, Carbon::parse($data['date']), $totalHours);
        $workHours->assertOtWithinWeeklyLimit($employee, Carbon::parse($data['date']), $totalHours);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = StoredFile::storeUpload($request->file('attachment'));
        }

        $overtime = $employee->overtimes()->create([
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'total_hours' => $totalHours,
            'reason' => $data['reason'],
            'attachment' => $attachmentPath,
            'status' => Overtime::STATUS_PENDING,
            'ot_rate' => 1.5,
        ]);

        AuditLog::record('create', 'overtime', "{$employee->full_name} requested OT for {$overtime->date->format('d M Y')}");

        return redirect()->route('employee.overtime.index')->with('success', 'Overtime request submitted.');
    }
}
