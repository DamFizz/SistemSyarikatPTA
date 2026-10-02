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

    /** Railway's internal proxy connects from carrier-grade NAT space. */
    private const RAILWAY_PROXY = '100.64.0.8';

    private const RAILWAY_EDGE = '152.233.15.121';

    private const FASTLY_EDGE = '151.101.2.15';

    private const CLIENT = '14.1.188.19';

    private function employeeOnOffice(string $allowedIps): User
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        Employee::factory()->create(['user_id' => $user->id, 'office_id' => Office::factory()->create(['allowed_ips' => $allowedIps])]);

        return $user;
    }

    /**
     * Chains as observed on the live Railway deployment (the edge strips client-sent
     * X-Forwarded-For, so the client is always the left-most entry).
     */
    public static function railwayChains(): array
    {
        return [
            'Railway edge (changes every request)' => [self::CLIENT.', '.self::RAILWAY_EDGE],
            'another Railway edge' => [self::CLIENT.', 152.233.68.98'],
            'Railway behind Fastly' => [self::CLIENT.', '.self::FASTLY_EDGE],
            'single hop' => [self::CLIENT],
        ];
    }

    #[DataProvider('railwayChains')]
    public function test_real_client_ip_is_resolved_on_railway(string $forwardedFor): void
    {
        config(['attendance.behind_platform_proxy' => true]);

        $this->actingAs($this->employeeOnOffice(self::CLIENT))
            ->withServerVariables(['REMOTE_ADDR' => self::RAILWAY_PROXY])
            ->withHeaders(['X-Forwarded-For' => $forwardedFor])
            ->getJson(route('employee.attendance.status'))
            ->assertOk()
            ->assertJson(['ip' => self::CLIENT, 'on_office_network' => true]);
    }

    public function test_without_a_platform_proxy_forwarded_headers_cannot_be_spoofed(): void
    {
        config(['attendance.behind_platform_proxy' => false]);

        // e.g. a LAN-hosted server: the "proxy" is just the client itself.
        $this->actingAs($this->employeeOnOffice('60.50.1.1'))
            ->withServerVariables(['REMOTE_ADDR' => '192.168.1.20'])
            ->withHeaders(['X-Forwarded-For' => '60.50.1.1'])
            ->getJson(route('employee.attendance.status'))
            ->assertJson(['on_office_network' => false]);
    }

    public function test_local_reverse_proxy_and_fastly_edge_are_skipped_without_platform_mode(): void
    {
        config(['attendance.behind_platform_proxy' => false]);

        $this->actingAs($this->employeeOnOffice(self::CLIENT))
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Forwarded-For' => self::CLIENT.', '.self::FASTLY_EDGE])
            ->getJson(route('employee.attendance.status'))
            ->assertJson(['ip' => self::CLIENT]);
    }

    public function test_network_probe_reports_the_resolved_ip(): void
    {
        config(['attendance.behind_platform_proxy' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => self::RAILWAY_PROXY])
            ->withHeaders(['X-Forwarded-For' => self::CLIENT.', '.self::RAILWAY_EDGE])
            ->getJson(route('network-probe'))
            ->assertOk()
            ->assertJson(['resolved_ip' => self::CLIENT, 'ip_version' => 4, 'connecting_proxy' => self::RAILWAY_PROXY]);
    }

    public function test_proxy_and_private_addresses_cannot_be_registered_as_the_office_network(): void
    {
        config(['attendance.behind_platform_proxy' => true]);
        $office = Office::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        foreach (['152.233.68.98', '100.64.0.4', '192.168.1.0/24'] as $entry) {
            $this->actingAs($admin)
                ->withServerVariables(['REMOTE_ADDR' => self::RAILWAY_PROXY])
                ->withHeaders(['X-Forwarded-For' => self::CLIENT.', '.self::RAILWAY_EDGE])
                ->put(route('super-admin.offices.network.update', $office), ['wifi_ssid' => 'HQ', 'wifi_security' => 'nopass', 'allowed_ips' => self::CLIENT."\n".$entry])
                ->assertSessionHasErrors('allowed_ips');
        }

        // The edge this request came through is rejected even outside the known ranges.
        $this->actingAs($admin)
            ->withServerVariables(['REMOTE_ADDR' => self::RAILWAY_PROXY])
            ->withHeaders(['X-Forwarded-For' => self::CLIENT.', '.self::FASTLY_EDGE])
            ->put(route('super-admin.offices.network.update', $office), ['wifi_ssid' => 'HQ', 'wifi_security' => 'nopass', 'allowed_ips' => self::FASTLY_EDGE])
            ->assertSessionHasErrors('allowed_ips');

        $this->actingAs($admin)
            ->withServerVariables(['REMOTE_ADDR' => self::RAILWAY_PROXY])
            ->withHeaders(['X-Forwarded-For' => self::CLIENT.', '.self::RAILWAY_EDGE])
            ->put(route('super-admin.offices.network.update', $office), ['wifi_ssid' => 'HQ', 'wifi_security' => 'nopass', 'allowed_ips' => self::CLIENT])
            ->assertSessionHasNoErrors();

        $this->assertTrue($office->fresh()->acceptsNetwork(self::CLIENT));
    }

    public function test_network_can_be_applied_to_every_office(): void
    {
        $offices = Office::factory()->count(3)->create(['allowed_ips' => '1.1.1.1']);
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)->put(route('super-admin.offices.network.update', $offices[0]), [
            'wifi_ssid' => 'Company', 'wifi_security' => 'nopass', 'allowed_ips' => self::CLIENT, 'apply_to_all' => '1',
        ])->assertSessionHasNoErrors();

        foreach ($offices as $office) {
            $this->assertTrue($office->fresh()->acceptsNetwork(self::CLIENT));
            $this->assertSame('Company', $office->fresh()->wifi_ssid);
        }
    }

    public function test_previously_saved_proxy_addresses_are_cleaned_up(): void
    {
        config(['attendance.behind_platform_proxy' => true]);
        $office = Office::factory()->create(['allowed_ips' => "152.233.68.98\n".self::CLIENT."\n100.64.0.2"]);
        $only = Office::factory()->create(['allowed_ips' => '152.233.15.121']);

        (require database_path('migrations/2026_10_03_100001_remove_proxy_addresses_from_office_networks.php'))->up();

        $this->assertSame(self::CLIENT, $office->fresh()->allowed_ips);
        $this->assertNull($only->fresh()->allowed_ips);
    }

    public function test_attendance_page_explains_an_unregistered_network(): void
    {
        $user = $this->employeeOnOffice('203.0.113.9');

        $this->actingAs($user)->get(route('employee.attendance.index'))
            ->assertOk()
            ->assertSee('which isn', false)
            ->assertSee('WiFi &amp; NFC', false);
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
