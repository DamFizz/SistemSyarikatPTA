<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_redirects_to_super_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('super-admin.dashboard'));
    }

    public function test_hr_admin_redirects_to_hr_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('hr.dashboard'));
    }

    public function test_employee_redirects_to_employee_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('employee.dashboard'));
    }

    public function test_employee_cannot_access_hr_module(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($user)->get('/hr/employees')
            ->assertForbidden();
    }

    public function test_manager_cannot_access_super_admin_module(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($user)->get('/super-admin/audit-logs')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
