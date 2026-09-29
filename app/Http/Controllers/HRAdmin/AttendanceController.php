<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $records = Attendance::with(['employee.department'])
            ->when($request->filled('date'), fn ($q) => $q->whereDate('attendance_date', $request->date('date')))
            ->when(! $request->filled('date') && ! $request->boolean('flagged'), fn ($q) => $q->whereDate('attendance_date', today()))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('flagged'), fn ($q) => $q->where('is_flagged', true))
            ->orderByDesc('attendance_date')
            ->orderBy('clock_in_time')
            ->paginate(20)
            ->withQueryString();

        return view('hradmin.attendance.index', [
            'records' => $records,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }
}
