<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403);

        $payrolls = $employee->payrolls()->with('payrollPeriod', 'payslip')->orderByDesc('id')->paginate(12);

        return view('employee.payslip.index', compact('payrolls'));
    }
}
