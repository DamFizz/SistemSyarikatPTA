<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClientIpResolutionTest extends TestCase
{
    use RefreshDatabase;

    private const RAILWAY_PROXY = '10.0.4.12';

    private const FASTLY_EDGE = '151.101.2.15';

    private const CLIENT = '175.139.10.20';

    public static function proxyChains(): array
    {
        return [
            'direct Railway edge' => [self::CLIENT],
            'Railway behind Fastly' => [self::CLIENT.', '.self::FASTLY_EDGE],
            'spoofed office IP is ignored (direct)' => ['60.50.1.1, '.self::CLIENT],
            'spoofed office IP is ignored (Fastly)' => ['60.50.1.1, '.self::CLIENT.', '.self::FASTLY_EDGE],
        ];
    }

    #[DataProvider('proxyChains')]
    public function test_real_client_ip_is_resolved_behind_railway_and_fastly(string $forwardedFor): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        Employee::factory()->create(['user_id' => $user->id, 'office_id' => Office::factory()->create(['allowed_ips' => self::CLIENT])]);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => self::RAILWAY_PROXY])
            ->withHeaders(['X-Forwarded-For' => $forwardedFor])
            ->getJson(route('employee.attendance.status'))
            ->assertOk()
            ->assertJson(['ip' => self::CLIENT, 'on_office_network' => true]);
    }

    public function test_spoofing_the_office_ip_does_not_unlock_attendance(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        Employee::factory()->create(['user_id' => $user->id, 'office_id' => Office::factory()->create(['allowed_ips' => '60.50.1.1'])]);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => self::RAILWAY_PROXY])
            ->withHeaders(['X-Forwarded-For' => '60.50.1.1, 8.8.4.4, '.self::FASTLY_EDGE])
            ->getJson(route('employee.attendance.status'))
            ->assertJson(['ip' => '8.8.4.4', 'on_office_network' => false]);
    }

    public function test_ipv6_devices_on_the_same_wifi_share_the_64_network(): void
    {
        $this->assertSame('2001:e68:5432:9a00::/64', Office::networkEntryFor('2001:e68:5432:9a00:1c2d:3e4f:aaaa:bbbb'));
        $this->assertSame('175.139.10.20', Office::networkEntryFor('175.139.10.20'));

        $office = Office::factory()->make(['allowed_ips' => "175.139.10.20\n2001:e68:5432:9a00::/64"]);
        $this->assertTrue($office->acceptsNetwork('2001:e68:5432:9a00:ffff:1:2:3'));
        $this->assertFalse($office->acceptsNetwork('2001:e68:5432:9b00::1'));

        // A bare IPv6 address registered earlier is widened to its /64 automatically.
        $legacy = Office::factory()->make(['allowed_ips' => '2001:e68:5432:9a00:1111:2222:3333:4444']);
        $this->assertTrue($legacy->acceptsNetwork('2001:e68:5432:9a00:9999:8888:7777:6666'));
    }
}
