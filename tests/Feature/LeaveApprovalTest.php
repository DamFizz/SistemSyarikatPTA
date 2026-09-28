<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_leave_deducts_balance(): void
    {
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create(['default_days_per_year' => 14]);
        $balance = LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => now()->year,
            'allocated_days' => 14,
            'used_days' => 0,
            'remaining_days' => 14,
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'total_days' => 3,
            'reason' => 'Family matters',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $approver = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);
        $leave->approveAndDeductBalance($approver->id);

        $balance->refresh();

        $this->assertEquals(LeaveRequest::STATUS_APPROVED, $leave->fresh()->status);
        $this->assertEquals(3, $balance->used_days);
        $this->assertEquals(11, $balance->remaining_days);
    }

    public function test_rejecting_leave_does_not_change_balance(): void
    {
        $employee = Employee::factory()->create();
        $leaveType = LeaveType::factory()->create();
        $balance = LeaveBalance::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => now()->year,
            'remaining_days' => 14,
        ]);

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(5),
            'total_days' => 1,
            'reason' => 'Personal',
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $approver = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $leave->rejectRequest($approver->id);

        $balance->refresh();

        $this->assertEquals(LeaveRequest::STATUS_REJECTED, $leave->fresh()->status);
        $this->assertEquals(14, $balance->remaining_days);
    }
}
