<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('hradmin.reports.index');
    }

    public function attendance(Request $request): View|Response
    {
        $rows = Attendance::with('employee.department')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('attendance_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('attendance_date', '<=', $request->date('to')))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('attendance_date')
            ->get();

        if ($request->get('export') === 'csv') {
            return $this->csv('attendance-report', ['Employee', 'Department', 'Date', 'Clock In', 'Clock Out', 'Working Minutes', 'Status'], $rows->map(fn ($r) => [
                $r->employee->full_name, $r->employee->department->name, $r->attendance_date->format('Y-m-d'),
                $r->clock_in_time?->format('H:i'), $r->clock_out_time?->format('H:i'), $r->working_minutes, $r->status,
            ]));
        }

        return view('hradmin.reports.attendance', ['rows' => $rows, 'departments' => Department::orderBy('name')->get()]);
    }

    public function leave(Request $request): View|Response
    {
        $rows = LeaveRequest::with('employee.department', 'leaveType')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('start_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('end_date', '<=', $request->date('to')))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('start_date')
            ->get();

        if ($request->get('export') === 'csv') {
            return $this->csv('leave-report', ['Employee', 'Department', 'Type', 'Start', 'End', 'Days', 'Status'], $rows->map(fn ($r) => [
                $r->employee->full_name, $r->employee->department->name, $r->leaveType->name,
                $r->start_date->format('Y-m-d'), $r->end_date->format('Y-m-d'), $r->total_days, $r->status,
            ]));
        }

        return view('hradmin.reports.leave', ['rows' => $rows, 'departments' => Department::orderBy('name')->get()]);
    }

    public function overtime(Request $request): View|Response
    {
        $rows = Overtime::with('employee.department')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('date', '<=', $request->date('to')))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $request->integer('department_id'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('date')
            ->get();

        if ($request->get('export') === 'csv') {
            return $this->csv('overtime-report', ['Employee', 'Department', 'Date', 'Hours', 'Amount', 'Status'], $rows->map(fn ($r) => [
                $r->employee->full_name, $r->employee->department->name, $r->date->format('Y-m-d'), $r->total_hours, $r->amount, $r->status,
            ]));
        }

        return view('hradmin.reports.overtime', ['rows' => $rows, 'departments' => Department::orderBy('name')->get()]);
    }

    public function payroll(Request $request): View|Response
    {
        $rows = Payroll::with('employee.department', 'payrollPeriod')
            ->when($request->filled('payroll_period_id'), fn ($q) => $q->where('payroll_period_id', $request->integer('payroll_period_id')))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $request->integer('department_id'))))
            ->orderByDesc('id')
            ->get();

        if ($request->get('export') === 'csv') {
            return $this->csv('payroll-report', ['Employee', 'Department', 'Period', 'Gross', 'Deduction', 'Net', 'Status'], $rows->map(fn ($r) => [
                $r->employee->full_name, $r->employee->department->name, $r->payrollPeriod->period_name, $r->gross_salary, $r->total_deduction, $r->net_salary, $r->status,
            ]));
        }

        return view('hradmin.reports.payroll', [
            'rows' => $rows,
            'departments' => Department::orderBy('name')->get(),
            'periods' => \App\Models\PayrollPeriod::orderByDesc('start_date')->get(),
        ]);
    }

    public function helpdesk(Request $request): View|Response
    {
        $rows = Ticket::with('employee.department', 'category', 'technician')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->get();

        if ($request->get('export') === 'csv') {
            return $this->csv('helpdesk-report', ['Ticket', 'Employee', 'Category', 'Priority', 'Status', 'Technician', 'Created'], $rows->map(fn ($r) => [
                $r->ticket_code, $r->employee->full_name, $r->category->name, $r->priority, $r->status, $r->technician?->name, $r->created_at->format('Y-m-d H:i'),
            ]));
        }

        return view('hradmin.reports.helpdesk', ['rows' => $rows]);
    }

    private function csv(string $filename, array $headers, $rows): StreamedResponse
    {
        $callback = function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename.'-'.now()->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
