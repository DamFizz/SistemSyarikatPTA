<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Office;
use App\Models\Overtime;
use App\Models\Shift;
use App\Services\AttendanceService;
use App\Support\AttendanceCapture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_JPEG = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAMCAgICAgMCAgIDAwMDBAYEBAQEBAgGBgUGCQgKCgkICQkKDA8MCgsOCwkJDRENDg8QEBEQCgwSExIQEw8QEBD/2wBDAQMDAwQDBAgEBAgQCwkLEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBD/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAj/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /**
     * A JPEG data URL whose header reports the given size; a unique comment makes every call a "new" photo.
     */
    public static function selfie(int $size = 480): string
    {
        $binary = base64_decode(self::TINY_JPEG);
        $sof = strpos($binary, "\xFF\xC0");
        $binary = substr_replace($binary, pack('nn', $size, $size), $sof + 5, 4);
        $comment = 'selfie-'.bin2hex(random_bytes(8));
        $binary = substr_replace($binary, "\xFF\xFE".pack('n', strlen($comment) + 2).$comment, 2, 0);

        return 'data:image/jpeg;base64,'.base64_encode($binary);
    }

    private function capture(array $overrides = []): AttendanceCapture
    {
        $values = array_merge([
            'latitude' => 3.1579,
            'longitude' => 101.7116,
            'accuracy' => 12.0,
            'selfieDataUrl' => self::selfie(),
            'ip' => '127.0.0.1',
            'userAgent' => 'PHPUnit',
            'deviceHash' => hash('sha256', 'device-a'),
        ], $overrides);

        return new AttendanceCapture(...$values);
    }

    private function makeEmployeeWithShift(Office $office): Employee
    {
        $employee = Employee::factory()->create(['office_id' => $office->id]);
        $shift = Shift::factory()->create();
        EmployeeShift::create(['employee_id' => $employee->id, 'shift_id' => $shift->id, 'effective_date' => now()->subDay()]);

        return $employee;
    }

    public function test_clock_in_succeeds_on_office_wifi_within_geofence(): void
    {
        $office = Office::factory()->create(['allowed_radius_meters' => 150]);
        $employee = $this->makeEmployeeWithShift($office);

        $attendance = app(AttendanceService::class)->clockIn($employee, $this->capture());

        $this->assertEquals(0, $attendance->clock_in_distance_meters);
        $this->assertEquals('wifi', $attendance->verification_method);
        $this->assertFalse($attendance->is_flagged);
        $this->assertTrue(Storage::disk('local')->exists($attendance->selfie_path));
        $this->assertNotNull($employee->fresh()->registered_device_hash);
    }

    public function test_clock_in_rejected_when_not_on_office_wifi(): void
    {
        $office = Office::factory()->create(['allowed_ips' => '203.0.113.0/24']);
        $employee = $this->makeEmployeeWithShift($office);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not connected to the office WiFi');

        app(AttendanceService::class)->clockIn($employee, $this->capture(['ip' => '198.51.100.7']));
    }

    public function test_clock_in_accepts_ip_inside_allowed_cidr_range(): void
    {
        $office = Office::factory()->create(['allowed_ips' => "10.0.0.1\n203.0.113.0/24"]);
        $employee = $this->makeEmployeeWithShift($office);

        $attendance = app(AttendanceService::class)->clockIn($employee, $this->capture(['ip' => '203.0.113.45']));

        $this->assertEquals('203.0.113.45', $attendance->ip_address);
    }

    public function test_testing_mode_skips_wifi_check_but_flags_record(): void
    {
        $office = Office::factory()->create(['allowed_ips' => null, 'network_check_enabled' => false]);
        $employee = $this->makeEmployeeWithShift($office);

        $attendance = app(AttendanceService::class)->clockIn($employee, $this->capture(['ip' => '198.51.100.7']));

        $this->assertEquals('testing', $attendance->verification_method);
        $this->assertTrue($attendance->is_flagged);
    }

    public function test_clock_in_rejected_outside_geofence_radius(): void
    {
        $office = Office::factory()->create(['allowed_radius_meters' => 100]);
        $employee = $this->makeEmployeeWithShift($office);

        $this->expectException(ValidationException::class);

        // Roughly 5km away.
        app(AttendanceService::class)->clockIn($employee, $this->capture(['latitude' => 3.1390, 'longitude' => 101.6869]));
    }

    public function test_clock_in_rejected_with_weak_gps_accuracy(): void
    {
        $employee = $this->makeEmployeeWithShift(Office::factory()->create());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GPS signal is too weak');

        app(AttendanceService::class)->clockIn($employee, $this->capture(['accuracy' => 2500.0]));
    }

    public function test_reused_selfie_is_rejected(): void
    {
        $office = Office::factory()->create();
        $first = $this->makeEmployeeWithShift($office);
        $second = $this->makeEmployeeWithShift($office);
        $photo = self::selfie();

        app(AttendanceService::class)->clockIn($first, $this->capture(['selfieDataUrl' => $photo]));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('already been used');

        app(AttendanceService::class)->clockIn($second, $this->capture(['selfieDataUrl' => $photo, 'deviceHash' => hash('sha256', 'device-b')]));
    }

    public function test_non_image_selfie_payload_is_rejected(): void
    {
        $employee = $this->makeEmployeeWithShift(Office::factory()->create());

        $this->expectException(ValidationException::class);

        app(AttendanceService::class)->clockIn($employee, $this->capture([
            'selfieDataUrl' => 'data:image/php;base64,'.base64_encode('<?php echo 1;'),
        ]));
    }

    public function test_low_resolution_selfie_is_rejected(): void
    {
        $employee = $this->makeEmployeeWithShift(Office::factory()->create());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('resolution is too low');

        app(AttendanceService::class)->clockIn($employee, $this->capture(['selfieDataUrl' => self::selfie(40)]));
    }

    public function test_shared_device_between_employees_is_flagged(): void
    {
        $office = Office::factory()->create();
        $first = $this->makeEmployeeWithShift($office);
        $second = $this->makeEmployeeWithShift($office);

        app(AttendanceService::class)->clockIn($first, $this->capture());
        $attendance = app(AttendanceService::class)->clockIn($second, $this->capture());

        $this->assertTrue($attendance->is_flagged);
        $this->assertStringContainsString('another employee', implode(' ', $attendance->flag_reasons));
    }

    public function test_unrecognised_device_is_flagged(): void
    {
        $office = Office::factory()->create();
        $employee = $this->makeEmployeeWithShift($office);
        $employee->forceFill(['registered_device_hash' => hash('sha256', 'old-phone')])->save();

        $attendance = app(AttendanceService::class)->clockIn($employee->fresh(), $this->capture());

        $this->assertTrue($attendance->is_flagged);
    }

    public function test_clock_out_requires_selfie_and_detects_overtime(): void
    {
        $office = Office::factory()->create();
        $employee = $this->makeEmployeeWithShift($office);

        $service = app(AttendanceService::class);
        $attendance = $service->clockIn($employee, $this->capture());
        $attendance->update(['clock_in_time' => now()->subHours(10)]);

        $result = $service->clockOut($employee, $this->capture());

        $this->assertEquals(540, $result['working_minutes']); // 10h - 1h break
        $this->assertEquals(60, $result['potential_ot_minutes']); // 9h worked vs 8h normal
        $this->assertInstanceOf(Overtime::class, $result['overtime']);
        $this->assertNotNull($attendance->fresh()->clock_out_selfie_path);
        $this->assertTrue(Storage::disk('local')->exists($attendance->fresh()->clock_out_selfie_path));
    }

    public function test_clock_out_rejected_when_not_on_office_wifi(): void
    {
        $office = Office::factory()->create();
        $employee = $this->makeEmployeeWithShift($office);

        $service = app(AttendanceService::class);
        $service->clockIn($employee, $this->capture());

        $this->expectException(ValidationException::class);

        $service->clockOut($employee, $this->capture(['ip' => '198.51.100.7']));
    }
}
