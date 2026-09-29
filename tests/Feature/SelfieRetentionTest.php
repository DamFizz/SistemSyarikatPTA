<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendancePhoto;
use App\Models\Employee;
use App\Models\User;
use App\Services\SelfieRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SelfieRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function recordWithSelfies(Employee $employee, string $date): Attendance
    {
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => $date,
            'clock_in_time' => $date.' 09:00:00',
            'clock_out_time' => $date.' 18:00:00',
            'selfie_path' => AttendancePhoto::STORAGE_MARKER,
            'selfie_hash' => hash('sha256', $date.'in'),
            'clock_out_selfie_path' => AttendancePhoto::STORAGE_MARKER,
            'clock_out_selfie_hash' => hash('sha256', $date.'out'),
            'status' => 'present',
        ]);

        foreach (['in', 'out'] as $type) {
            $attendance->photos()->create(['type' => $type, 'mime' => 'image/jpeg', 'size' => 3, 'data' => base64_encode('jpg')]);
        }

        return $attendance;
    }

    public function test_selfies_are_served_from_the_database(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $attendance = $this->recordWithSelfies($employee, today()->toDateString());

        $this->actingAs($user)->get(route('attendance.selfie', [$attendance, 'out']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_purge_removes_only_selfies_from_previous_months(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15));
        $employee = Employee::factory()->create();

        $lastMonth = $this->recordWithSelfies($employee, '2026-09-30');
        $thisMonth = $this->recordWithSelfies($employee, '2026-10-01');

        $deleted = app(SelfieRetentionService::class)->purge();

        $this->assertSame(2, $deleted);
        $this->assertSame(0, $lastMonth->photos()->count());
        $this->assertNull($lastMonth->fresh()->selfie_path);
        $this->assertFalse($lastMonth->fresh()->hasSelfie('in'));
        // Hashes are kept so an old photo can never be re-submitted.
        $this->assertNotNull($lastMonth->fresh()->selfie_hash);

        $this->assertSame(2, $thisMonth->photos()->count());
        $this->assertTrue($thisMonth->fresh()->hasSelfie('in'));
    }

    public function test_purge_also_removes_legacy_files_on_disk(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('selfies/1/old.jpg', 'jpg');
        $this->travelTo(now()->setDate(2026, 10, 15));

        $attendance = Attendance::create([
            'employee_id' => Employee::factory()->create()->id,
            'attendance_date' => '2026-08-10',
            'clock_in_time' => '2026-08-10 09:00:00',
            'selfie_path' => 'selfies/1/old.jpg',
            'status' => 'present',
        ]);

        app(SelfieRetentionService::class)->purge();

        Storage::disk('local')->assertMissing('selfies/1/old.jpg');
        $this->assertNull($attendance->fresh()->selfie_path);
    }

    public function test_missing_legacy_file_is_not_shown_as_a_broken_image(): void
    {
        Storage::fake('local');
        $attendance = Attendance::create([
            'employee_id' => Employee::factory()->create()->id,
            'attendance_date' => today(),
            'clock_in_time' => now(),
            'selfie_path' => 'selfies/1/lost-on-redeploy.jpg',
            'status' => 'present',
        ]);

        $this->assertFalse($attendance->hasSelfie('in'));
    }

    public function test_retention_can_keep_extra_months(): void
    {
        config(['attendance.selfie_retention_months' => 1]);
        $this->travelTo(now()->setDate(2026, 10, 15));
        $employee = Employee::factory()->create();
        $lastMonth = $this->recordWithSelfies($employee, '2026-09-10');

        app(SelfieRetentionService::class)->purge();

        $this->assertSame(2, $lastMonth->photos()->count());
    }

    public function test_purge_runs_once_a_day_from_page_visits_and_via_artisan(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15));
        $employee = Employee::factory()->create();
        $old = $this->recordWithSelfies($employee, '2026-09-01');
        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);

        Cache::flush();
        $this->actingAs($hr)->get(route('hr.attendance.index'))->assertOk();
        $this->assertSame(0, $old->photos()->count());

        $another = $this->recordWithSelfies($employee, '2026-09-02');
        $this->artisan('attendance:purge-selfies')->assertSuccessful();
        $this->assertSame(0, $another->photos()->count());
    }
}
