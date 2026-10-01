<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftReminderTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE, 'name' => 'Nur Aisyah']);
        $employee = Employee::factory()->create(['user_id' => $user->id, 'full_name' => 'Nur Aisyah']);
        EmployeeShift::create(['employee_id' => $employee->id, 'shift_id' => Shift::factory()->create(['start_time' => '08:00'])->id, 'effective_date' => now()->subMonth()]);

        return $employee;
    }

    private function reminder(Employee $employee): array
    {
        return app(ShiftReminderService::class)->forEmployee($employee->fresh());
    }

    public function test_upcoming_shift_returns_todays_deadline(): void
    {
        $this->travelTo(today()->setTime(7, 30));

        $reminder = $this->reminder($this->employee());

        $this->assertSame('upcoming', $reminder['state']);
        $this->assertSame('Nur', $reminder['name']);
        $this->assertSame('08:00 AM', $reminder['start_label']);
        $this->assertSame(today()->setTime(8, 0)->toIso8601String(), $reminder['start']);
        $this->assertSame(600, $reminder['warning_seconds']);
        $this->assertSame(10, $reminder['final_seconds']);
    }

    public function test_clocked_in_employee_gets_no_countdown(): void
    {
        $employee = $this->employee();
        Attendance::create(['employee_id' => $employee->id, 'attendance_date' => today(), 'clock_in_time' => today()->setTime(7, 52), 'status' => 'present']);

        $reminder = $this->reminder($employee);

        $this->assertSame('clocked_in', $reminder['state']);
        $this->assertSame('07:52 AM', $reminder['clocked_in_at']);
    }

    public function test_employee_on_approved_leave_is_ignored(): void
    {
        $employee = $this->employee();
        LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => LeaveType::factory()->create()->id,
            'start_date' => today()->subDay(), 'end_date' => today()->addDay(), 'total_days' => 3,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $this->assertSame(['off', 'leave'], array_values(array_intersect_key($this->reminder($employee), array_flip(['state', 'off_reason']))));
    }

    public function test_pending_leave_still_counts_as_working(): void
    {
        $employee = $this->employee();
        LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => LeaveType::factory()->create()->id,
            'start_date' => today(), 'end_date' => today(), 'total_days' => 1, 'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->assertSame('upcoming', $this->reminder($employee)['state']);
    }

    public function test_rest_day_and_missing_shift_are_ignored(): void
    {
        $employee = $this->employee();
        foreach (range(1, 6) as $daysAgo) {
            Attendance::create(['employee_id' => $employee->id, 'attendance_date' => today()->subDays($daysAgo), 'clock_in_time' => today()->subDays($daysAgo)->setTime(8, 0), 'status' => 'present']);
        }
        $this->assertSame('rest_day', $this->reminder($employee)['off_reason']);

        $noShift = Employee::factory()->create();
        $this->assertSame('no_shift', $this->reminder($noShift)['off_reason']);
    }

    public function test_login_page_shows_reminder_only_for_remembered_device(): void
    {
        $this->travelTo(today()->setTime(7, 30));
        $employee = $this->employee();

        $this->get(route('login'))->assertOk()->assertDontSee('Forget this device');

        $login = $this->post(route('login'), ['email' => $employee->user->email, 'password' => 'password']);
        $login->assertCookie(ShiftReminderService::DEVICE_COOKIE, (string) $employee->user_id);

        auth()->logout();

        $this->withCookie(ShiftReminderService::DEVICE_COOKIE, (string) $employee->user_id)
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Forget this device')
            ->assertSee('shiftCountdown', false);
    }

    public function test_forget_device_clears_cookie(): void
    {
        $this->post(route('login.forget-device'))
            ->assertRedirect(route('login'))
            ->assertCookieExpired(ShiftReminderService::DEVICE_COOKIE);
    }

    public function test_app_shows_countdown_to_employee_who_has_not_clocked_in(): void
    {
        $this->travelTo(today()->setTime(7, 30));
        $employee = $this->employee();

        $this->actingAs($employee->user)->get(route('employee.leave.index'))->assertSee('shiftCountdown', false);
        // Not on the attendance page, where the overlay would cover the camera.
        $this->actingAs($employee->user)->get(route('employee.attendance.index'))->assertDontSee('shiftCountdown(', false);
    }
}
