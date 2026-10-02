<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HRAdmin\StoreEmployeeRequest;
use App\Http\Requests\HRAdmin\UpdateEmployeeRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Shift;
use App\Models\User;
use App\Services\EmployeeRecordsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    /** Form fields stored as dated history rather than on the employee row. */
    private const RECORD_FIELDS = ['shift_id', 'basic_salary', 'allowance'];

    public function index(Request $request): View
    {
        $employees = Employee::with(['user', 'department', 'office'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('employment_status', $request->string('status')))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('hradmin.employees.index', compact('employees', 'departments'));
    }

    public function create(): View
    {
        return view('hradmin.employees.create', [
            'departments' => Department::orderBy('name')->get(),
            'offices' => Office::orderBy('name')->get(),
            'managers' => Employee::orderBy('full_name')->get(),
            'shifts' => Shift::orderBy('start_time')->get(),
        ]);
    }

    public function store(StoreEmployeeRequest $request, EmployeeRecordsService $records): RedirectResponse
    {
        $data = $request->validated();

        $employee = DB::transaction(function () use ($data, $records) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'email_verified_at' => now(),
            ]);

            $employee = Employee::create([
                ...collect($data)->except(['name', 'email', 'password', 'password_confirmation', 'role', ...self::RECORD_FIELDS])->all(),
                'user_id' => $user->id,
                'full_name' => $data['name'],
            ]);

            $joined = Carbon::parse($data['join_date']);
            $records->assignShift($employee, $data['shift_id'] ?? null, $joined);
            $records->setSalary($employee, $data['basic_salary'] ?? null, $data['allowance'] ?? null, $joined);
            $records->createLeaveBalances($employee);

            return $employee;
        });

        AuditLog::record('create', 'employee', "Created employee \"{$employee->full_name}\" ({$employee->employee_code})", null, $employee->toArray());

        return redirect()->route('hr.employees.index')->with('success', 'Employee created.');
    }

    public function edit(Employee $employee): View
    {
        return view('hradmin.employees.edit', [
            'employee' => $employee->load('user'),
            'departments' => Department::orderBy('name')->get(),
            'offices' => Office::orderBy('name')->get(),
            'managers' => Employee::where('id', '!=', $employee->id)->orderBy('full_name')->get(),
            'shifts' => Shift::orderBy('start_time')->get(),
            'currentShiftId' => $employee->currentShift()?->id,
            'salary' => $employee->currentSalary(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, EmployeeRecordsService $records): RedirectResponse
    {
        $data = $request->validated();
        $old = $employee->toArray();

        DB::transaction(function () use ($data, $employee, $records) {
            $employee->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                ...(filled($data['password'] ?? null) ? ['password' => $data['password']] : []),
            ]);

            $employee->update([
                ...collect($data)->except(['name', 'email', 'role', 'password', 'password_confirmation', ...self::RECORD_FIELDS])->all(),
                'full_name' => $data['name'],
            ]);

            $records->assignShift($employee, $data['shift_id'] ?? null, today());
            $records->setSalary($employee, $data['basic_salary'] ?? null, $data['allowance'] ?? null, today());
        });

        AuditLog::record('update', 'employee', "Updated employee \"{$employee->full_name}\" ({$employee->employee_code})", $old, $employee->toArray());

        return redirect()->route('hr.employees.index')->with('success', 'Employee updated.');
    }

    /**
     * Forget the employee's registered phone, e.g. after they change devices.
     * The next device used to clock in becomes the new registered device.
     */
    public function resetDevice(Employee $employee): RedirectResponse
    {
        $employee->forceFill(['registered_device_hash' => null, 'device_registered_at' => null])->save();

        AuditLog::record('update', 'employee', "Reset registered attendance device for \"{$employee->full_name}\" ({$employee->employee_code})");

        return back()->with('success', 'Registered device reset. The next phone used to clock in will be registered.');
    }
}
