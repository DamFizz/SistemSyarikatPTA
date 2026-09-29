<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\RestDayJustification;
use App\Models\Setting;
use App\Services\WorkHoursService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkHoursController extends Controller
{
    public function __construct(private readonly WorkHoursService $workHours) {}

    public function index(Request $request): View
    {
        $employees = Employee::with(['department', 'salaries'])
            ->whereIn('employment_status', ['active', 'probation'])
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        $rows = $employees->getCollection()->map(fn (Employee $employee) => [
            'employee' => $employee,
            'salary' => $employee->currentSalary()?->basic_salary,
            'summary' => $this->workHours->summary($employee),
        ]);

        return view('hradmin.work-hours.index', [
            'employees' => $employees,
            'rows' => $rows,
            'departments' => Department::orderBy('name')->get(),
            'justifications' => RestDayJustification::with(['employee.department', 'reviewer'])
                ->orderByRaw("case when status = 'pending' then 0 else 1 end")
                ->latest('work_date')
                ->limit(30)
                ->get(),
            'settings' => [
                'ot_monthly_cap_hours' => $this->workHours->otMonthlyCap(),
                'ot_cap_salary_threshold' => $this->workHours->otSalaryThreshold(),
                'weekly_hours_limit' => $this->workHours->weeklyHoursLimit(),
                'rest_day_enforced' => $this->workHours->restDayEnforced(),
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ot_monthly_cap_hours' => ['required', 'numeric', 'min:1', 'max:300'],
            'ot_cap_salary_threshold' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'weekly_hours_limit' => ['required', 'numeric', 'min:1', 'max:168'],
            'rest_day_enforced' => ['nullable', 'boolean'],
        ]);

        $data['rest_day_enforced'] = $request->boolean('rest_day_enforced') ? 1 : 0;

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        AuditLog::record('update', 'work_hours', 'Updated working-hour limits', null, $data);

        return back()->with('success', 'Working-hour limits updated.');
    }

    public function updateEmployee(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'ot_cap_mode' => ['required', Rule::in(array_keys(WorkHoursService::CAP_MODES))],
        ]);

        $old = $employee->ot_cap_mode;
        $employee->update($data);

        AuditLog::record('update', 'work_hours', "Set overtime cap for {$employee->full_name} to \"{$data['ot_cap_mode']}\"", ['ot_cap_mode' => $old], $data);

        return back()->with('success', "Overtime cap updated for {$employee->full_name}.");
    }

    public function review(Request $request, RestDayJustification $justification): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([RestDayJustification::STATUS_ACKNOWLEDGED, RestDayJustification::STATUS_REJECTED])],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $justification->update([
            ...$data,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AuditLog::record('review', 'work_hours', "Marked rest-day justification of {$justification->employee->full_name} ({$justification->work_date->format('d M Y')}) as {$data['status']}");

        return back()->with('success', 'Justification reviewed.');
    }
}
