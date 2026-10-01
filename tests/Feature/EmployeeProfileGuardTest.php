<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeProfileGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_is_redirected_from_attendance_with_a_message(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)->get(route('employee.attendance.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('warning', fn ($message) => str_contains($message, 'Super Admin'));

        $this->actingAs($admin)->get(route('super-admin.dashboard'))->assertOk();
    }

    public function test_leftover_intended_url_does_not_end_on_a_403(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        // A previous session ended on the attendance page, so it is stored as "intended".
        $this->get(route('employee.attendance.index'))->assertRedirect(route('login'));

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('employee.attendance.index'));

        $this->get(route('employee.attendance.index'))->assertRedirect(route('dashboard'));
    }

    public function test_json_requests_get_a_403_with_the_reason(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($user)->getJson(route('employee.attendance.status'))
            ->assertForbidden()
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'employee profile'));
    }

    public function test_employee_without_profile_sees_a_notice_not_a_clock_in_button(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);

        $this->actingAs($user)->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSee('employee profile isn')
            ->assertDontSee(route('employee.attendance.index'));
    }

    public function test_error_pages_are_styled(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('Page not found')->assertSee('Back to dashboard');
    }
}
