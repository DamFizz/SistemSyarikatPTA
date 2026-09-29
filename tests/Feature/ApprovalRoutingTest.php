<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalRoutingTest extends TestCase
{
    use RefreshDatabase;

    private function person(string $role, array $attributes = []): Employee
    {
        $user = User::factory()->create(['role' => $role]);

        return Employee::factory()->create(['user_id' => $user->id, ...$attributes]);
    }

    private function overtimeFor(Employee $employee): Overtime
    {
        return Overtime::create([
            'employee_id' => $employee->id,
            'date' => today(),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'total_hours' => 2,
            'reason' => 'Release',
            'status' => Overtime::STATUS_PENDING,
            'ot_rate' => 1.5,
        ]);
    }

    public function test_manager_sees_direct_report_from_another_department(): void
    {
        $manager = $this->person(User::ROLE_MANAGER);
        $report = $this->person(User::ROLE_EMPLOYEE, ['manager_id' => $manager->id, 'department_id' => Department::factory()]);
        $ot = $this->overtimeFor($report);

        $this->actingAs($manager->user)->get(route('manager.overtime.index'))->assertOk()->assertSee($report->full_name);
        $this->actingAs($manager->user)->post(route('manager.overtime.approve', $ot))->assertRedirect();

        $this->assertSame(Overtime::STATUS_APPROVED, $ot->fresh()->status);
    }

    public function test_department_manager_covers_staff_without_direct_manager(): void
    {
        $manager = $this->person(User::ROLE_MANAGER);
        $manager->department->update(['manager_id' => $manager->id]);
        $staff = $this->person(User::ROLE_EMPLOYEE, ['department_id' => $manager->department_id]);

        $this->assertTrue($staff->isApprovableBy($manager));
        $this->assertSame($manager->id, $staff->approvingManager()?->id);
    }

    public function test_manager_cannot_approve_someone_elses_report_or_themselves(): void
    {
        $manager = $this->person(User::ROLE_MANAGER);
        $otherManager = $this->person(User::ROLE_MANAGER);
        $stranger = $this->person(User::ROLE_EMPLOYEE, ['manager_id' => $otherManager->id]);

        $this->actingAs($manager->user)->post(route('manager.overtime.approve', $this->overtimeFor($stranger)))->assertForbidden();
        $this->actingAs($manager->user)->post(route('manager.overtime.approve', $this->overtimeFor($manager)))->assertForbidden();
    }

    public function test_hr_admin_cannot_approve_overtime_or_leave(): void
    {
        $manager = $this->person(User::ROLE_MANAGER);
        $report = $this->person(User::ROLE_EMPLOYEE, ['manager_id' => $manager->id]);
        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);
        $ot = $this->overtimeFor($report);

        $this->actingAs($hr)->get(route('hr.overtime.index'))->assertOk()->assertSee('Awaiting');
        $this->actingAs($hr)->post(route('hr.overtime.approve', $ot))->assertForbidden();

        $this->assertSame(Overtime::STATUS_PENDING, $ot->fresh()->status);
    }

    public function test_super_admin_approves_only_when_no_manager_exists(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $orphan = $this->person(User::ROLE_EMPLOYEE);
        $orphanOt = $this->overtimeFor($orphan);

        $this->actingAs($admin)->post(route('hr.overtime.approve', $orphanOt))->assertRedirect();
        $this->assertSame(Overtime::STATUS_APPROVED, $orphanOt->fresh()->status);

        $manager = $this->person(User::ROLE_MANAGER);
        $managed = $this->person(User::ROLE_EMPLOYEE, ['manager_id' => $manager->id]);

        $this->actingAs($admin)->post(route('hr.overtime.approve', $this->overtimeFor($managed)))->assertForbidden();
    }
}
