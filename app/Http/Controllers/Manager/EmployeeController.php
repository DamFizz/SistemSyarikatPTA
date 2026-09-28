<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StoreEmployeeRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $manager = Auth::user()->employee;
        abort_if(! $manager, 403);

        $team = Employee::with('user')
            ->where('department_id', $manager->department_id)
            ->where('id', '!=', $manager->id)
            ->orderBy('full_name')
            ->paginate(15);

        return view('manager.employees.index', compact('team'));
    }

    public function create(): View
    {
        return view('manager.employees.create', [
            'offices' => Office::orderBy('name')->get(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $manager = Auth::user()->employee;
        abort_if(! $manager, 403);

        $data = $request->validated();

        $employee = DB::transaction(function () use ($data, $manager) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => User::ROLE_EMPLOYEE,
                'email_verified_at' => now(),
            ]);

            $employee = Employee::create([
                ...collect($data)->except(['name', 'email', 'password', 'password_confirmation'])->all(),
                'user_id' => $user->id,
                'full_name' => $data['name'],
                'department_id' => $manager->department_id,
                'manager_id' => $manager->id,
                'employment_status' => 'probation',
            ]);

            foreach (LeaveType::all() as $leaveType) {
                $employee->leaveBalances()->create([
                    'leave_type_id' => $leaveType->id,
                    'year' => now()->year,
                    'allocated_days' => $leaveType->default_days_per_year,
                    'used_days' => 0,
                    'remaining_days' => $leaveType->default_days_per_year,
                ]);
            }

            return $employee;
        });

        AuditLog::record('create', 'employee', "{$manager->full_name} (manager) added team member \"{$employee->full_name}\" ({$employee->employee_code})", null, $employee->toArray());

        return redirect()->route('manager.employees.index')->with('success', 'Team member added.');
    }
}
