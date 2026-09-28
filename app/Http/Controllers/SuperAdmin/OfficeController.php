<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreOfficeRequest;
use App\Http\Requests\SuperAdmin\UpdateOfficeRequest;
use App\Models\AuditLog;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function index(): View
    {
        $offices = Office::withCount('employees')->orderBy('name')->paginate(10);

        return view('superadmin.offices.index', compact('offices'));
    }

    public function create(): View
    {
        return view('superadmin.offices.create');
    }

    public function store(StoreOfficeRequest $request): RedirectResponse
    {
        $office = Office::create($request->validated());

        AuditLog::record('create', 'office', "Created office/branch \"{$office->name}\"", null, $office->toArray());

        return redirect()->route('super-admin.offices.index')->with('success', 'Office/branch created.');
    }

    public function edit(Office $office): View
    {
        return view('superadmin.offices.edit', compact('office'));
    }

    public function update(UpdateOfficeRequest $request, Office $office): RedirectResponse
    {
        $old = $office->toArray();
        $office->update($request->validated());

        AuditLog::record('update', 'office', "Updated office/branch \"{$office->name}\"", $old, $office->toArray());

        return redirect()->route('super-admin.offices.index')->with('success', 'Office/branch updated.');
    }
}
