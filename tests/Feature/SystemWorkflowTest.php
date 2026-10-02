<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\PayrollPeriod;
use App\Models\Shift;
use App\Models\StoredFile;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * End-to-end checks of every form in the system, the way staff use them.
 */
class SystemWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Employee $manager;

    private Employee $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->department = Department::factory()->create();
        $office = Office::factory()->create();

        $this->manager = Employee::factory()->create([
            'user_id' => User::factory()->create(['role' => User::ROLE_MANAGER]),
            'department_id' => $this->department->id,
            'office_id' => $office->id,
        ]);
        $this->department->update(['manager_id' => $this->manager->id]);

        $this->staff = Employee::factory()->create([
            'department_id' => $this->department->id,
            'office_id' => $office->id,
            'manager_id' => $this->manager->id,
            'employment_status' => 'probation',
        ]);
        EmployeeSalary::factory()->create(['employee_id' => $this->staff->id, 'basic_salary' => 2600, 'effective_date' => now()->subYear()]);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_hr_sets_shift_salary_and_can_reset_a_password(): void
    {
        $hr = $this->user(User::ROLE_HR_ADMIN);
        $shift = Shift::factory()->create();

        $this->actingAs($hr)->get(route('hr.employees.create'))->assertOk()->assertSee('Work Schedule');

        $this->actingAs($hr)->post(route('hr.employees.store'), [
            'name' => 'Lim Mei', 'email' => 'mei@example.com', 'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'employee', 'employee_code' => 'EMP7001', 'ic_number' => '000101-07-1111',
            'department_id' => $this->department->id, 'office_id' => $this->staff->office_id,
            'position' => 'Clerk', 'employment_type' => 'full_time', 'employment_status' => 'probation',
            'join_date' => now()->toDateString(), 'shift_id' => $shift->id, 'basic_salary' => '3200', 'allowance' => '150',
        ])->assertSessionHasNoErrors();

        $mei = Employee::where('employee_code', 'EMP7001')->firstOrFail();
        $this->assertSame($shift->id, $mei->currentShift()?->id);
        $this->assertEquals(3200, $mei->currentSalary()->basic_salary);

        $this->actingAs($hr)->get(route('hr.employees.edit', $mei))->assertOk();

        $this->actingAs($hr)->put(route('hr.employees.update', $mei), [
            'name' => 'Lim Mei', 'email' => 'mei@example.com', 'role' => 'employee', 'employee_code' => 'EMP7001',
            'ic_number' => '000101-07-1111', 'department_id' => $this->department->id, 'office_id' => $mei->office_id,
            'position' => 'Senior Clerk', 'employment_type' => 'full_time', 'employment_status' => 'active',
            'join_date' => now()->toDateString(), 'shift_id' => $shift->id, 'basic_salary' => '3500', 'allowance' => '150',
            'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(3500, $mei->fresh()->currentSalary()->basic_salary);
        $this->assertTrue(Hash::check('brand-new-pass', $mei->user->fresh()->password));
        $this->assertSame(1, $mei->salaries()->count(), 'Same-day change edits the record instead of duplicating it.');
    }

    public function test_validation_errors_are_shown_on_the_form(): void
    {
        $hr = $this->user(User::ROLE_HR_ADMIN);

        $this->actingAs($hr)->from(route('hr.employees.create'))->followingRedirects()
            ->post(route('hr.employees.store'), ['name' => 'X', 'phone' => str_repeat('1', 30)])
            ->assertOk()
            ->assertSee('Please fix')
            ->assertSee('The phone field must not be greater than 20 characters.');
    }

    public function test_manager_adds_a_team_member_on_their_shift(): void
    {
        $shift = Shift::factory()->create();
        $this->manager->shifts()->create(['shift_id' => $shift->id, 'effective_date' => now()->subMonth()]);
        LeaveType::factory()->create();

        $this->actingAs($this->manager->user)->get(route('manager.employees.create'))->assertOk();

        $this->actingAs($this->manager->user)->post(route('manager.employees.store'), [
            'name' => 'Ali', 'email' => 'ali@example.com', 'password' => 'password123', 'password_confirmation' => 'password123',
            'employee_code' => 'EMP7002', 'ic_number' => '000202-07-2222', 'office_id' => $this->staff->office_id,
            'position' => 'Technician', 'employment_type' => 'full_time', 'join_date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $ali = Employee::where('employee_code', 'EMP7002')->firstOrFail();
        $this->assertSame($shift->id, $ali->currentShift()?->id);
        $this->assertSame($this->manager->id, $ali->manager_id);
        $this->assertSame(1, $ali->leaveBalances()->count());
    }

    public function test_departments_and_offices_can_be_created_and_edited(): void
    {
        $hr = $this->user(User::ROLE_HR_ADMIN);
        $this->actingAs($hr)->post(route('hr.departments.store'), ['name' => 'Logistics'])->assertSessionHasNoErrors();
        $logistics = Department::where('name', 'Logistics')->firstOrFail();
        $this->actingAs($hr)->get(route('hr.departments.edit', $logistics))->assertOk();
        $this->actingAs($hr)->put(route('hr.departments.update', $logistics), ['name' => 'Logistics & Supply'])->assertSessionHasNoErrors();
        $this->assertSame('Logistics & Supply', $logistics->fresh()->name);

        $admin = $this->user(User::ROLE_SUPER_ADMIN);
        $office = ['name' => 'KL Branch', 'address' => 'Jalan Ampang', 'latitude' => 3.15, 'longitude' => 101.71, 'allowed_radius_meters' => 200];
        $this->actingAs($admin)->post(route('super-admin.offices.store'), $office)->assertSessionHasNoErrors();
        $kl = Office::where('name', 'KL Branch')->firstOrFail();
        $this->actingAs($admin)->get(route('super-admin.offices.edit', $kl))->assertOk();
        $this->actingAs($admin)->put(route('super-admin.offices.update', $kl), [...$office, 'allowed_radius_meters' => 300])->assertSessionHasNoErrors();
        $this->assertSame(300, (int) $kl->fresh()->allowed_radius_meters);
    }

    public function test_leave_with_attachment_is_visible_to_the_manager_and_balance_cannot_go_negative(): void
    {
        $annual = LeaveType::factory()->create(['default_days_per_year' => 3]);
        $staffUser = $this->staff->user;

        $this->actingAs($staffUser)->get(route('employee.leave.create'))->assertOk();

        foreach ([[2, 3], [5, 6]] as [$from, $to]) {
            $this->actingAs($staffUser)->post(route('employee.leave.store'), [
                'leave_type_id' => $annual->id,
                'start_date' => now()->addDays($from)->toDateString(),
                'end_date' => now()->addDays($to)->toDateString(),
                'reason' => 'Family matters',
                'attachment' => UploadedFile::fake()->image('mc.jpg'),
            ])->assertSessionHasNoErrors();
        }

        [$first, $second] = LeaveRequest::orderBy('id')->get()->all();
        $this->assertStringStartsWith(StoredFile::PREFIX, $first->attachment);
        $this->assertSame([], Storage::disk('public')->allFiles(), 'Attachments must not depend on the (ephemeral) disk.');

        $this->actingAs($this->manager->user)->get(route('manager.leave.index'))
            ->assertOk()->assertSee('Family matters')->assertSee(route('attachments.show', ['leave', $first->id]));
        $this->actingAs($this->manager->user)->get(route('attachments.show', ['leave', $first->id]))->assertOk();
        $this->actingAs($this->user(User::ROLE_EMPLOYEE))->get(route('attachments.show', ['leave', $first->id]))->assertForbidden();

        $this->actingAs($this->manager->user)->post(route('manager.leave.approve', $first))->assertSessionHasNoErrors();
        $this->actingAs($this->manager->user)->post(route('manager.leave.approve', $second))->assertSessionHasErrors('request');

        $this->assertSame(LeaveRequest::STATUS_PENDING, $second->fresh()->status);
        $this->assertEquals(1, $this->staff->leaveBalanceFor($annual, now()->year)->remaining_days);

        // Approving twice is a friendly message, not an error page.
        $this->actingAs($this->manager->user)->post(route('manager.leave.approve', $first))->assertSessionHasErrors('request');
    }

    public function test_leave_can_be_applied_for_next_year(): void
    {
        $annual = LeaveType::factory()->create(['default_days_per_year' => 14]);
        $start = now()->addYear()->startOfYear()->addDays(5);

        $this->actingAs($this->staff->user)->post(route('employee.leave.store'), [
            'leave_type_id' => $annual->id, 'start_date' => $start->toDateString(), 'end_date' => $start->toDateString(), 'reason' => 'Trip',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leave_balances', ['employee_id' => $this->staff->id, 'year' => $start->year]);
    }

    public function test_overtime_request_with_attachment_is_approved_by_the_manager(): void
    {
        $this->actingAs($this->staff->user)->get(route('employee.overtime.create'))->assertOk();
        $this->actingAs($this->staff->user)->post(route('employee.overtime.store'), [
            'date' => now()->subDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '20:00',
            'reason' => 'Server migration', 'attachment' => UploadedFile::fake()->create('approval.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $ot = Overtime::firstOrFail();
        $this->actingAs($this->manager->user)->get(route('manager.overtime.index'))->assertOk()->assertSee('Server migration');
        $this->actingAs($this->manager->user)->get(route('attachments.show', ['overtime', $ot->id]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->manager->user)->post(route('manager.overtime.approve', $ot))->assertSessionHasNoErrors();
        $this->assertSame(Overtime::STATUS_APPROVED, $ot->fresh()->status);
        $this->assertGreaterThan(0, (float) $ot->fresh()->amount);
    }

    public function test_helpdesk_ticket_lifecycle(): void
    {
        $category = TicketCategory::create(['name' => 'Hardware']);
        $technician = $this->user(User::ROLE_TECHNICIAN);

        $this->actingAs($this->staff->user)->get(route('employee.tickets.create'))->assertOk();
        $this->actingAs($this->staff->user)->post(route('employee.tickets.store'), [
            'category_id' => $category->id, 'title' => 'Laptop broken', 'description' => 'Screen flickers', 'priority' => 'high',
            'attachment' => UploadedFile::fake()->create('page.html', 1, 'text/html'),
        ])->assertSessionHasNoErrors();

        $ticket = Ticket::firstOrFail();
        // Active content is never rendered by the browser.
        $this->actingAs($technician)->get(route('attachments.show', ['ticket', $ticket->id]))
            ->assertOk()->assertHeader('Content-Type', 'application/octet-stream');

        $this->actingAs($technician)->get(route('technician.tickets.index'))->assertOk()->assertSee('Laptop broken');
        $this->actingAs($technician)->get(route('technician.tickets.show', $ticket))->assertOk();
        $this->actingAs($technician)->post(route('technician.tickets.assign', $ticket))->assertSessionHasNoErrors();
        $this->actingAs($technician)->post(route('technician.tickets.reply', $ticket), ['message' => 'On my way'])->assertSessionHasNoErrors();
        $this->actingAs($technician)->post(route('technician.tickets.status', $ticket), ['status' => 'waiting_user'])->assertSessionHasNoErrors();
        $this->actingAs($this->staff->user)->post(route('employee.tickets.reply', $ticket), ['message' => 'Thanks'])->assertSessionHasNoErrors();
        $this->actingAs($this->staff->user)->get(route('employee.tickets.show', $ticket))->assertOk()->assertSee('On my way');

        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->actingAs($this->user(User::ROLE_HR_ADMIN))->get(route('hr.tickets.show', $ticket))->assertOk();
    }

    public function test_announcement_attachment_survives_and_can_be_opened(): void
    {
        $hr = $this->user(User::ROLE_HR_ADMIN);

        $this->actingAs($hr)->post(route('announcements.store'), [
            'title' => 'Townhall', 'description' => 'Friday 3pm', 'priority' => 'normal',
            'attachment' => UploadedFile::fake()->image('poster.png'),
        ])->assertSessionHasNoErrors();

        $announcement = Announcement::firstOrFail();
        $this->actingAs($this->staff->user)->get(route('announcements.index'))->assertOk()->assertSee(route('attachments.show', ['announcement', $announcement->id]));
        $this->actingAs($this->staff->user)->get(route('attachments.show', ['announcement', $announcement->id]))->assertOk();

        $this->actingAs($hr)->delete(route('announcements.destroy', $announcement));
        $this->assertSame(0, StoredFile::count());
    }

    public function test_payroll_includes_probation_staff_and_follows_its_steps(): void
    {
        $hr = $this->user(User::ROLE_HR_ADMIN);

        $this->actingAs($hr)->post(route('hr.payroll.store'), [
            'period_name' => 'This month', 'start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->endOfMonth()->toDateString(),
        ])->assertSessionHasNoErrors();
        $period = PayrollPeriod::firstOrFail();

        // Cannot skip ahead.
        $this->actingAs($hr)->post(route('hr.payroll.mark-paid', $period))->assertSessionHas('error');
        $this->actingAs($hr)->post(route('hr.payroll.approve', $period))->assertSessionHas('error');

        $this->actingAs($hr)->post(route('hr.payroll.generate', $period))->assertSessionHas('success');
        $payroll = $period->payrolls()->where('employee_id', $this->staff->id)->first();
        $this->assertNotNull($payroll, 'Probation staff must be paid.');

        $this->actingAs($hr)->post(route('hr.payroll.approve', $period))->assertSessionHas('success');
        $this->actingAs($hr)->post(route('hr.payroll.generate', $period))->assertSessionHas('error');
        $this->actingAs($hr)->post(route('hr.payroll.mark-paid', $period))->assertSessionHas('success');
        $this->actingAs($hr)->get(route('hr.payroll.show', $period))->assertOk();

        $this->actingAs($this->staff->user)->get(route('employee.payslips.index'))->assertOk();
        $this->actingAs($this->staff->user)->get(route('payslips.download', $payroll))->assertOk();
    }

    public function test_reports_render_and_export(): void
    {
        $hr = $this->user(User::ROLE_HR_ADMIN);

        foreach (['attendance', 'leave', 'overtime', 'payroll', 'helpdesk'] as $report) {
            $this->actingAs($hr)->get(route("hr.reports.{$report}"))->assertOk();
            $this->actingAs($hr)->get(route("hr.reports.{$report}", ['export' => 'csv']))->assertOk();
        }
    }

    public function test_profile_can_be_updated(): void
    {
        $user = $this->staff->user;

        $this->actingAs($user)->patch(route('profile.update'), ['name' => 'New Name', 'email' => $user->email])->assertSessionHasNoErrors();
        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password', 'password' => 'another-pass-1', 'password_confirmation' => 'another-pass-1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('New Name', $user->fresh()->name);
    }
}
