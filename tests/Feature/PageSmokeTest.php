<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Renders every main page for each role to catch Blade/view errors.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The in-memory schema is migrated once per run, so seed explicitly per test.
        $this->seed();
    }

    public static function pagesByRole(): array
    {
        $shared = ['announcements.index', 'profile.edit'];

        return [
            'super admin' => ['superadmin@sems.test', [
                ...$shared, 'super-admin.dashboard', 'super-admin.offices.index', 'super-admin.offices.create',
                'super-admin.audit-logs.index', 'hr.dashboard', 'hr.employees.index', 'hr.attendance.index',
            ]],
            'hr admin' => ['hradmin@sems.test', [
                ...$shared, 'hr.dashboard', 'hr.employees.index', 'hr.employees.create', 'hr.departments.index',
                'hr.departments.create', 'hr.attendance.index', 'hr.leave.index', 'hr.overtime.index',
                'hr.payroll.index', 'hr.payroll.create', 'hr.tickets.index', 'hr.reports.index',
                'hr.reports.attendance', 'hr.reports.leave', 'hr.reports.overtime', 'hr.reports.payroll',
                'hr.reports.helpdesk', 'announcements.create', 'employee.attendance.index',
            ]],
            'manager' => ['manager@sems.test', [
                ...$shared, 'manager.dashboard', 'manager.employees.index', 'manager.employees.create',
                'manager.leave.index', 'manager.overtime.index', 'employee.attendance.index',
            ]],
            'technician' => ['technician@sems.test', [
                ...$shared, 'technician.dashboard', 'technician.tickets.index',
            ]],
            'employee' => ['employee@sems.test', [
                ...$shared, 'employee.dashboard', 'employee.attendance.index', 'employee.leave.index',
                'employee.leave.create', 'employee.overtime.index', 'employee.overtime.create',
                'employee.payslips.index', 'employee.tickets.index', 'employee.tickets.create',
            ]],
        ];
    }

    #[DataProvider('pagesByRole')]
    public function test_pages_render_for_role(string $email, array $routes): void
    {
        $user = User::where('email', $email)->firstOrFail();

        foreach ($routes as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_office_network_page_renders(): void
    {
        $admin = User::where('email', 'superadmin@sems.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('super-admin.offices.network.edit', Office::first()))
            ->assertOk()
            ->assertSee('Write the NFC tag');
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Welcome back');
    }
}
