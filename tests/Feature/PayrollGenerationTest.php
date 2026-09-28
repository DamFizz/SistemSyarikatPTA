<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\PayrollPeriod;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_computes_gross_and_net_salary_correctly(): void
    {
        $employee = Employee::factory()->create(['employment_status' => 'active']);
        EmployeeSalary::factory()->create([
            'employee_id' => $employee->id,
            'basic_salary' => 4500,
            'allowance' => 200,
            'epf_rate' => 11,
            'socso_rate' => 0.5,
            'eis_rate' => 0.2,
            'effective_date' => now()->subMonth(),
        ]);

        $period = PayrollPeriod::create([
            'period_name' => 'Test Period',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);

        $count = app(PayrollService::class)->generate($period);

        $this->assertEquals(1, $count);

        $payroll = $period->payrolls()->first();

        $this->assertEquals(4700, $payroll->gross_salary); // 4500 + 200
        $this->assertEqualsWithDelta(549.90, $payroll->total_deduction, 0.01); // 4700 * 11.7%
        $this->assertEqualsWithDelta(4150.10, $payroll->net_salary, 0.01);
        $this->assertEquals(PayrollPeriod::STATUS_PROCESSING, $period->fresh()->status);
    }

    public function test_generate_skips_employees_already_in_period(): void
    {
        $employee = Employee::factory()->create(['employment_status' => 'active']);
        EmployeeSalary::factory()->create(['employee_id' => $employee->id]);

        $period = PayrollPeriod::create([
            'period_name' => 'Test Period 2',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
            'status' => PayrollPeriod::STATUS_DRAFT,
        ]);

        $service = app(PayrollService::class);
        $service->generate($period);
        $secondRunCount = $service->generate($period);

        $this->assertEquals(0, $secondRunCount);
        $this->assertEquals(1, $period->payrolls()->count());
    }
}
