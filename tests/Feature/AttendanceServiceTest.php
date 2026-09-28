<?php

namespace Tests\Feature;

use App\Models\AttendanceQrToken;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\Shift;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_JPEG = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAMCAgICAgMCAgIDAwMDBAYEBAQEBAgGBgUGCQgKCgkICQkKDA8MCgsOCwkJDRENDg8QEBEQCgwSExIQEw8QEBD/2wBDAQMDAwQDBAgEBAgQCwkLEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBD/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAj/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=';

    private function makeEmployeeWithShift(Office $office): Employee
    {
        $employee = Employee::factory()->create(['office_id' => $office->id]);
        $shift = Shift::factory()->create();
        EmployeeShift::create(['employee_id' => $employee->id, 'shift_id' => $shift->id, 'effective_date' => now()->subDay()]);

        return $employee;
    }

    public function test_clock_in_succeeds_within_geofence_radius(): void
    {
        $office = Office::factory()->create(['latitude' => 3.1579, 'longitude' => 101.7116, 'allowed_radius_meters' => 150]);
        $employee = $this->makeEmployeeWithShift($office);
        $token = AttendanceQrToken::factory()->create(['office_id' => $office->id]);

        $attendance = app(AttendanceService::class)->clockIn(
            $employee, 3.1579, 101.7116, self::TINY_JPEG, '127.0.0.1', 'PHPUnit', $token->token
        );

        $this->assertNotNull($attendance->id);
        $this->assertEquals(0, $attendance->clock_in_distance_meters);
        $this->assertEquals('qr', $attendance->verification_method);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($attendance->selfie_path));
    }

    public function test_clock_in_rejected_outside_geofence_radius(): void
    {
        $office = Office::factory()->create(['latitude' => 3.1579, 'longitude' => 101.7116, 'allowed_radius_meters' => 100]);
        $employee = $this->makeEmployeeWithShift($office);
        $token = AttendanceQrToken::factory()->create(['office_id' => $office->id]);

        // Roughly 5km away.
        $farLat = 3.1390;
        $farLng = 101.6869;

        $this->expectException(ValidationException::class);

        app(AttendanceService::class)->clockIn($employee, $farLat, $farLng, self::TINY_JPEG, '127.0.0.1', 'PHPUnit', $token->token);
    }

    public function test_clock_in_rejected_with_expired_qr_token(): void
    {
        $office = Office::factory()->create(['latitude' => 3.1579, 'longitude' => 101.7116]);
        $employee = $this->makeEmployeeWithShift($office);
        $expiredToken = AttendanceQrToken::factory()->create(['office_id' => $office->id, 'expires_at' => now()->subMinute()]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('QR code expired');

        app(AttendanceService::class)->clockIn($employee, 3.1579, 101.7116, self::TINY_JPEG, '127.0.0.1', 'PHPUnit', $expiredToken->token);
    }

    public function test_clock_in_succeeds_via_nfc_tag(): void
    {
        $office = Office::factory()->create(['latitude' => 3.1579, 'longitude' => 101.7116, 'nfc_tag_id' => 'HQ-ENTRANCE-01']);
        $employee = $this->makeEmployeeWithShift($office);

        $attendance = app(AttendanceService::class)->clockIn(
            $employee, 3.1579, 101.7116, self::TINY_JPEG, '127.0.0.1', 'PHPUnit', null, 'HQ-ENTRANCE-01'
        );

        $this->assertEquals('nfc', $attendance->verification_method);
        $this->assertNull($attendance->qr_token_id);
    }

    public function test_clock_in_rejected_with_wrong_nfc_tag(): void
    {
        $office = Office::factory()->create(['latitude' => 3.1579, 'longitude' => 101.7116, 'nfc_tag_id' => 'HQ-ENTRANCE-01']);
        $employee = $this->makeEmployeeWithShift($office);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('NFC tag not recognized');

        app(AttendanceService::class)->clockIn(
            $employee, 3.1579, 101.7116, self::TINY_JPEG, '127.0.0.1', 'PHPUnit', null, 'WRONG-TAG'
        );
    }

    public function test_clock_out_detects_overtime_when_working_beyond_shift_hours(): void
    {
        $office = Office::factory()->create(['latitude' => 3.1579, 'longitude' => 101.7116]);
        $employee = $this->makeEmployeeWithShift($office);
        $token = AttendanceQrToken::factory()->create(['office_id' => $office->id]);

        $service = app(AttendanceService::class);
        $attendance = $service->clockIn($employee, 3.1579, 101.7116, self::TINY_JPEG, '127.0.0.1', 'PHPUnit', $token->token);
        $attendance->update(['clock_in_time' => now()->subHours(10)]);

        $result = $service->clockOut($employee, 3.1579, 101.7116);

        $this->assertEquals(540, $result['working_minutes']); // 10h - 1h break
        $this->assertEquals(60, $result['potential_ot_minutes']); // 9h worked vs 8h normal
        $this->assertInstanceOf(Overtime::class, $result['overtime']);
        $this->assertEquals(Overtime::STATUS_PENDING, $result['overtime']->status);
    }
}
