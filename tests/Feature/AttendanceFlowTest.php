<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function employee(array $officeAttributes = []): Employee
    {
        $office = Office::factory()->create($officeAttributes);
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        return Employee::factory()->create(['user_id' => $user->id, 'office_id' => $office->id]);
    }

    private function payload(string $challenge): array
    {
        return [
            'challenge' => $challenge,
            'latitude' => 3.1579,
            'longitude' => 101.7116,
            'accuracy' => 10,
            'selfie' => AttendanceServiceTest::selfie(),
        ];
    }

    public function test_status_reports_office_network_and_state(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee->user)
            ->getJson(route('employee.attendance.status'))
            ->assertOk()
            ->assertJson(['state' => 'not_clocked_in', 'on_office_network' => true, 'ssid' => 'SEMS-Office']);

        $employee->office->update(['allowed_ips' => '203.0.113.9']);

        $this->actingAs($employee->user->fresh())
            ->getJson(route('employee.attendance.status'))
            ->assertJson(['on_office_network' => false]);
    }

    public function test_full_clock_in_then_clock_out_flow(): void
    {
        $employee = $this->employee();
        $this->actingAs($employee->user);

        $challenge = $this->postJson(route('employee.attendance.begin'), ['action' => 'in'])->assertOk()->json('challenge');
        $this->postJson(route('employee.attendance.clock-in'), $this->payload($challenge))->assertOk();

        $this->getJson(route('employee.attendance.status'))->assertJson(['state' => 'clocked_in']);

        // Clock-in is no longer possible; only clock-out.
        $this->postJson(route('employee.attendance.begin'), ['action' => 'in'])->assertStatus(422);

        $challenge = $this->postJson(route('employee.attendance.begin'), ['action' => 'out'])->assertOk()->json('challenge');
        $this->postJson(route('employee.attendance.clock-out'), $this->payload($challenge))->assertOk();

        $this->getJson(route('employee.attendance.status'))->assertJson(['state' => 'completed']);
        $this->assertNotNull(Attendance::first()->clock_out_selfie_path);
    }

    public function test_clock_in_without_valid_challenge_is_rejected(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee->user)
            ->postJson(route('employee.attendance.clock-in'), $this->payload(str_repeat('a', 64)))
            ->assertStatus(422);

        $this->assertSame(0, Attendance::count());
    }

    public function test_challenge_is_single_use(): void
    {
        $employee = $this->employee();
        $this->actingAs($employee->user);

        $challenge = $this->postJson(route('employee.attendance.begin'), ['action' => 'in'])->json('challenge');
        $this->postJson(route('employee.attendance.clock-in'), $this->payload($challenge))->assertOk();

        $this->postJson(route('employee.attendance.clock-out'), $this->payload($challenge))->assertStatus(422);
    }

    public function test_expired_challenge_is_rejected(): void
    {
        $employee = $this->employee();
        $this->actingAs($employee->user);

        $challenge = $this->postJson(route('employee.attendance.begin'), ['action' => 'in'])->json('challenge');

        $this->travel(5)->minutes();

        $this->postJson(route('employee.attendance.clock-in'), $this->payload($challenge))->assertStatus(422);
    }

    public function test_begin_rejected_when_off_office_network(): void
    {
        $employee = $this->employee(['allowed_ips' => '203.0.113.9']);

        $this->actingAs($employee->user)
            ->postJson(route('employee.attendance.begin'), ['action' => 'in'])
            ->assertStatus(422)
            ->assertJsonPath('errors.attendance.0', 'You are not connected to the office WiFi. Tap the NFC tag to connect, then try again.');
    }

    public function test_selfie_is_private_to_owner_and_hr(): void
    {
        $employee = $this->employee();
        $this->actingAs($employee->user);
        $challenge = $this->postJson(route('employee.attendance.begin'), ['action' => 'in'])->json('challenge');
        $this->postJson(route('employee.attendance.clock-in'), $this->payload($challenge));
        $attendance = Attendance::first();

        $this->get(route('attendance.selfie', [$attendance, 'in']))->assertOk();

        $stranger = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $this->actingAs($stranger)->get(route('attendance.selfie', [$attendance, 'in']))->assertForbidden();

        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);
        $this->actingAs($hr)->get(route('attendance.selfie', [$attendance, 'in']))->assertOk();
    }

    public function test_super_admin_can_update_office_wifi_settings(): void
    {
        $office = Office::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)->get(route('super-admin.offices.network.edit', $office))->assertOk()->assertSee('SEMS-Office');

        $this->actingAs($admin)->put(route('super-admin.offices.network.update', $office), [
            'wifi_ssid' => 'HQ-Staff',
            'wifi_security' => 'WPA',
            'wifi_password' => 'new-password-123',
            'allowed_ips' => "175.139.10.20\n60.50.0.0/16",
        ])->assertRedirect(route('super-admin.offices.network.edit', $office));

        $office->refresh();
        $this->assertSame('HQ-Staff', $office->wifi_ssid);
        $this->assertSame('new-password-123', $office->wifi_password);
        $this->assertTrue($office->acceptsNetwork('60.50.1.2'));
        $this->assertFalse($office->acceptsNetwork('8.8.8.8'));
        $this->assertStringNotContainsString('new-password-123', json_encode(AuditLog::latest('id')->first()->new_value));
    }

    public function test_invalid_ip_is_rejected_and_blank_password_keeps_existing(): void
    {
        $office = Office::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)->put(route('super-admin.offices.network.update', $office), [
            'wifi_ssid' => 'HQ', 'wifi_security' => 'WPA', 'allowed_ips' => 'not-an-ip',
        ])->assertSessionHasErrors('allowed_ips');

        $this->actingAs($admin)->put(route('super-admin.offices.network.update', $office), [
            'wifi_ssid' => 'HQ', 'wifi_security' => 'WPA', 'wifi_password' => '', 'allowed_ips' => '1.2.3.4',
        ])->assertSessionHasNoErrors();

        $this->assertSame('office-secret', $office->fresh()->wifi_password);
    }

    public function test_employee_cannot_manage_office_network(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee->user)
            ->get(route('super-admin.offices.network.edit', $employee->office))
            ->assertForbidden();
    }
}
