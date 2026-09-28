<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HRAdmin\StoreDepartmentRequest;
use App\Http\Requests\HRAdmin\UpdateDepartmentRequest;
use App\Models\AuditLog;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::withCount('employees')->with('manager')->orderBy('name')->paginate(10);

        return view('hradmin.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('hradmin.departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());

        AuditLog::record('create', 'department', "Created department \"{$department->name}\"", null, $department->toArray());

        return redirect()->route('hr.departments.index')->with('success', 'Department created.');
    }

    public function edit(Department $department): View
    {
        $employees = $department->employees()->orderBy('full_name')->get();

        return view('hradmin.departments.edit', compact('department', 'employees'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $old = $department->toArray();
        $department->update($request->validated());

        AuditLog::record('update', 'department', "Updated department \"{$department->name}\"", $old, $department->toArray());

        return redirect()->route('hr.departments.index')->with('success', 'Department updated.');
    }
}
