<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\HRAdmin\StorePayrollPeriodRequest;
use App\Models\AuditLog;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService) {}

    public function index(): View
    {
        return view('hradmin.payroll.index', [
            'periods' => PayrollPeriod::withCount('payrolls')->orderByDesc('start_date')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('hradmin.payroll.create');
    }

    public function store(StorePayrollPeriodRequest $request): RedirectResponse
    {
        $period = PayrollPeriod::create($request->validated());

        AuditLog::record('create', 'payroll', "Created payroll period \"{$period->period_name}\"");

        return redirect()->route('hr.payroll.show', $period)->with('success', 'Payroll period created.');
    }

    public function show(PayrollPeriod $payrollPeriod): View
    {
        return view('hradmin.payroll.show', [
            'period' => $payrollPeriod,
            'payrolls' => $payrollPeriod->payrolls()->with('employee')->orderBy('employee_id')->get(),
        ]);
    }

    public function generate(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $count = $this->payrollService->generate($payrollPeriod);

        AuditLog::record('generate', 'payroll', "Generated payroll for {$count} employee(s) in \"{$payrollPeriod->period_name}\"");

        return back()->with('success', "Payroll generated for {$count} employee(s).");
    }

    public function approve(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $payrollPeriod->update(['status' => PayrollPeriod::STATUS_APPROVED]);
        $payrollPeriod->payrolls()->update(['status' => PayrollPeriod::STATUS_APPROVED]);

        AuditLog::record('approve', 'payroll', "Approved payroll period \"{$payrollPeriod->period_name}\"");

        return back()->with('success', 'Payroll period approved.');
    }

    public function markPaid(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $payrollPeriod->update(['status' => PayrollPeriod::STATUS_PAID]);

        foreach ($payrollPeriod->payrolls as $payroll) {
            $payroll->update(['status' => PayrollPeriod::STATUS_PAID]);
            $payroll->payslip()->firstOrCreate([], [
                'generated_at' => now(),
                'generated_by' => auth()->id(),
            ]);
        }

        AuditLog::record('pay', 'payroll', "Marked payroll period \"{$payrollPeriod->period_name}\" as paid");

        return back()->with('success', 'Payroll period marked as paid. Payslips generated.');
    }
}
