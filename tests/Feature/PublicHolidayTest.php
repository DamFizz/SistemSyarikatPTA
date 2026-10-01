<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Office;
use App\Models\PublicHoliday;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\ShiftReminderService;
use App\Support\AttendanceCapture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHolidayTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $employee = Employee::factory()->create(['user_id' => $user->id, 'office_id' => Office::factory()]);
        EmployeeShift::create(['employee_id' => $employee->id, 'shift_id' => Shift::factory()->create()->id, 'effective_date' => now()->subMonth()]);

        return $employee;
    }

    public function test_one_off_and_recurring_holidays_match_the_right_days(): void
    {
        PublicHoliday::create(['name' => 'Hari Raya', 'date' => '2026-03-21']);
        PublicHoliday::create(['name' => 'National Day', 'date' => '2025-08-31', 'is_recurring' => true]);

        $this->assertSame('Hari Raya', PublicHoliday::forDate(now()->setDate(2026, 3, 21))?->name);
        $this->assertNull(PublicHoliday::forDate(now()->setDate(2027, 3, 21)));
        $this->assertSame('National Day', PublicHoliday::forDate(now()->setDate(2031, 8, 31))?->name);
        $this->assertNull(PublicHoliday::forDate(now()->setDate(2031, 8, 30)));
    }

    public function test_no_clock_in_reminder_on_a_public_holiday(): void
    {
        $this->travelTo(now()->setDate(2026, 8, 31)->setTime(7, 30));
        PublicHoliday::create(['name' => 'National Day', 'date' => '2026-08-31', 'is_recurring' => true]);

        $reminder = app(ShiftReminderService::class)->forEmployee($this->employee());

        $this->assertSame('off', $reminder['state']);
        $this->assertSame('holiday', $reminder['off_reason']);
        $this->assertSame('National Day', $reminder['holiday_name']);
    }

    public function test_working_on_a_holiday_is_allowed_but_flagged(): void
    {
        PublicHoliday::create(['name' => 'Deepavali', 'date' => today()]);
        $employee = $this->employee();

        $attendance = app(AttendanceService::class)->clockIn($employee, new AttendanceCapture(
            latitude: 3.1579, longitude: 101.7116, accuracy: 10, selfieDataUrl: AttendanceServiceTest::selfie(), ip: '127.0.0.1',
        ));

        $this->assertTrue($attendance->is_flagged);
        $this->assertContains('Worked on a public holiday (Deepavali)', $attendance->flag_reasons);
    }

    public function test_hr_can_add_and_remove_holidays_but_employees_cannot(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);

        $this->actingAs($hr)->post(route('hr.public-holidays.store'), ['name' => 'Hari Raya Aidilfitri', 'date' => '2027-03-10'])
            ->assertSessionHasNoErrors();
        $holiday = PublicHoliday::firstOrFail();
        $this->actingAs($hr)->get(route('hr.public-holidays.index'))->assertOk()->assertSee('Hari Raya Aidilfitri');

        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $this->actingAs($employee)->delete(route('hr.public-holidays.destroy', $holiday))->assertForbidden();

        $this->actingAs($hr)->delete(route('hr.public-holidays.destroy', $holiday))->assertRedirect();
        $this->assertModelMissing($holiday);
    }

    public function test_login_page_shows_live_clock_instead_of_sample_data(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('08:52')
            ->assertSee('Sign in once on this device');
    }
}
