<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeShift;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\RestDayJustification;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\WorkHoursService;
use App\Support\AttendanceCapture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkHoursComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->travelTo(now()->startOfWeek()->addDays(2)->setTime(12, 0)); // a Wednesday noon
    }

    private function employee(float $salary = 3000, string $mode = 'auto'): Employee
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $employee = Employee::factory()->create(['user_id' => $user->id, 'office_id' => Office::factory(), 'ot_cap_mode' => $mode]);
        EmployeeSalary::factory()->create(['employee_id' => $employee->id, 'basic_salary' => $salary, 'effective_date' => now()->subYear()]);

        return $employee;
    }

    private function bookOt(Employee $employee, float $hours, string $status = Overtime::STATUS_APPROVED): Overtime
    {
        return Overtime::create([
            'employee_id' => $employee->id, 'date' => today(), 'start_time' => '18:00', 'end_time' => '20:00',
            'total_hours' => $hours, 'reason' => 'x', 'status' => $status, 'ot_rate' => 1.5,
        ]);
    }

    private function worked(Employee $employee, string $date, int $minutes = 480): void
    {
        Attendance::create([
            'employee_id' => $employee->id, 'attendance_date' => $date,
            'clock_in_time' => $date.' 08:00:00', 'clock_out_time' => $date.' 18:00:00',
            'working_minutes' => $minutes, 'status' => 'present',
        ]);
    }

    private function capture(array $overrides = []): AttendanceCapture
    {
        return new AttendanceCapture(...array_merge([
            'latitude' => 3.1579, 'longitude' => 101.7116, 'accuracy' => 10.0,
            'selfieDataUrl' => AttendanceServiceTest::selfie(), 'ip' => '127.0.0.1', 'deviceHash' => hash('sha256', 'd'),
        ], $overrides));
    }

    // ---------------- Monthly overtime cap ----------------

    public function test_cap_follows_salary_threshold_and_mode(): void
    {
        $service = app(WorkHoursService::class);

        $this->assertTrue($service->otCapApplies($this->employee(3500)));
        $this->assertFalse($service->otCapApplies($this->employee(6000)));
        $this->assertTrue($service->otCapApplies($this->employee(6000, 'enforced')));
        $this->assertFalse($service->otCapApplies($this->employee(2000, 'exempt')));

        Setting::set('ot_cap_salary_threshold', 7000);
        $this->assertTrue($service->otCapApplies($this->employee(6000)));
    }

    public function test_overtime_request_beyond_monthly_cap_is_rejected(): void
    {
        $employee = $this->employee();
        $this->bookOt($employee, 103);

        $this->actingAs($employee->user)->post(route('employee.overtime.store'), [
            'date' => today()->toDateString(), 'start_time' => '18:00', 'end_time' => '20:00', 'reason' => 'Deadline',
        ])->assertSessionHasErrors('end_time');

        $this->assertSame(1, Overtime::count());
    }

    public function test_exempt_employee_can_exceed_cap(): void
    {
        $employee = $this->employee(9000);
        $this->bookOt($employee, 103);

        $this->actingAs($employee->user)->post(route('employee.overtime.store'), [
            'date' => today()->toDateString(), 'start_time' => '18:00', 'end_time' => '20:00', 'reason' => 'Deadline',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Overtime::count());
    }

    public function test_manager_cannot_approve_beyond_cap(): void
    {
        $managerUser = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $manager = Employee::factory()->create(['user_id' => $managerUser->id]);
        $employee = $this->employee();
        $employee->update(['manager_id' => $manager->id]);

        $this->bookOt($employee, 100);
        $pending = $this->bookOt($employee, 6, Overtime::STATUS_PENDING);

        $this->actingAs($managerUser)->post(route('manager.overtime.approve', $pending))->assertSessionHasErrors('overtime');
        $this->assertSame(Overtime::STATUS_PENDING, $pending->fresh()->status);
    }

    // ---------------- Weekly hours limit ----------------

    public function test_clock_in_blocked_after_weekly_limit(): void
    {
        $employee = $this->employee();
        $this->worked($employee, today()->subDays(2)->toDateString(), 30 * 60);
        $this->worked($employee, today()->subDay()->toDateString(), 30 * 60);

        $this->actingAs($employee->user)
            ->postJson(route('employee.attendance.begin'), ['action' => 'in'])
            ->assertStatus(422)
            ->assertJsonPath('errors.attendance.0', fn ($message) => str_contains($message, 'legal limit'));
    }

    public function test_overtime_on_unrecorded_day_counts_towards_weekly_limit(): void
    {
        $employee = $this->employee(9000);
        $this->worked($employee, today()->subDays(2)->toDateString(), 29 * 60);
        $this->worked($employee, today()->subDay()->toDateString(), 29 * 60);

        $this->expectException(ValidationException::class);

        app(WorkHoursService::class)->assertOtWithinWeeklyLimit($employee, today(), 3);
    }

    // ---------------- Rest day ----------------

    public function test_seventh_consecutive_day_requires_reason_and_notifies_hr(): void
    {
        $employee = $this->employee();
        EmployeeShift::create(['employee_id' => $employee->id, 'shift_id' => Shift::factory()->create()->id, 'effective_date' => now()->subMonth()]);

        foreach (range(1, 6) as $daysAgo) {
            $this->worked($employee, today()->subDays($daysAgo)->toDateString(), 60);
        }

        $service = app(AttendanceService::class);
        $this->assertTrue(app(WorkHoursService::class)->restDayRequiredToday($employee));

        try {
            $service->clockIn($employee, $this->capture());
            $this->fail('Clock-in without a reason should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rest_day_reason', $e->errors());
        }

        $attendance = $service->clockIn($employee, $this->capture(['restDayReason' => 'Urgent month-end closing']));

        $this->assertTrue($attendance->is_flagged);
        $this->assertDatabaseHas('rest_day_justifications', [
            'employee_id' => $employee->id, 'attendance_id' => $attendance->id, 'consecutive_days' => 7, 'status' => 'pending',
        ]);
    }

    public function test_hr_can_update_limits_mode_and_review_justifications(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);
        $employee = $this->employee();
        $this->worked($employee, today()->toDateString());
        $justification = RestDayJustification::create([
            'employee_id' => $employee->id, 'work_date' => today(), 'consecutive_days' => 7, 'reason' => 'Audit week',
        ]);

        $this->actingAs($hr)->get(route('hr.work-hours.index'))->assertOk()->assertSee('Audit week');

        $this->actingAs($hr)->put(route('hr.work-hours.settings'), [
            'ot_monthly_cap_hours' => 90, 'ot_cap_salary_threshold' => 5000, 'weekly_hours_limit' => 55, 'rest_day_enforced' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame(90.0, app(WorkHoursService::class)->otMonthlyCap());
        $this->assertSame(55.0, app(WorkHoursService::class)->weeklyHoursLimit());

        $this->actingAs($hr)->put(route('hr.work-hours.employee', $employee), ['ot_cap_mode' => 'exempt'])->assertSessionHasNoErrors();
        $this->assertSame('exempt', $employee->fresh()->ot_cap_mode);

        $this->actingAs($hr)->post(route('hr.work-hours.review', $justification), ['status' => 'acknowledged', 'review_note' => 'OK'])->assertSessionHasNoErrors();
        $this->assertSame('acknowledged', $justification->fresh()->status);
    }

    public function test_employee_cannot_open_work_hours_admin(): void
    {
        $this->actingAs($this->employee()->user)->get(route('hr.work-hours.index'))->assertForbidden();
    }

    // ---------------- Announcements ----------------

    public function test_urgent_banner_disappears_after_viewing_or_dismissing(): void
    {
        $author = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);
        $reader = $this->employee()->user;
        Announcement::create(['title' => 'Fire drill at 3pm', 'description' => 'Assemble outside', 'category' => 'general', 'priority' => 'urgent', 'created_by' => $author->id]);

        $this->actingAs($reader)->get(route('employee.overtime.index'))->assertSee('Fire drill at 3pm');
        $this->actingAs($reader)->get(route('announcements.index'))->assertOk();
        $this->actingAs($reader->fresh())->get(route('employee.overtime.index'))->assertDontSee('Fire drill at 3pm');

        $this->travel(1)->minutes();
        Announcement::create(['title' => 'Server maintenance tonight', 'description' => 'x', 'category' => 'general', 'priority' => 'urgent', 'created_by' => $author->id]);
        $this->actingAs($reader->fresh())->get(route('employee.overtime.index'))->assertSee('Server maintenance tonight');

        $this->actingAs($reader->fresh())->post(route('announcements.dismiss'))->assertRedirect();
        $this->actingAs($reader->fresh())->get(route('employee.overtime.index'))->assertDontSee('Server maintenance tonight');
    }
}
