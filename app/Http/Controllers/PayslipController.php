<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PayslipController extends Controller
{
    public function download(Payroll $payroll): Response
    {
        $user = Auth::user();
        $isOwner = $user->employee && $user->employee->id === $payroll->employee_id;

        abort_unless($isOwner || $user->hasRole('hr_admin', 'super_admin'), 403);
        abort_unless($payroll->payslip, 404, 'Payslip not yet generated for this period.');

        $payroll->load(['employee.department', 'payrollPeriod', 'items']);

        $pdf = Pdf::loadView('payslip.pdf', ['payroll' => $payroll]);

        return $pdf->download("payslip-{$payroll->employee->employee_code}-{$payroll->payrollPeriod->period_name}.pdf");
    }
}
