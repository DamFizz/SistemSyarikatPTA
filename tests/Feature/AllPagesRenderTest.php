<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every parameter-less page, opened by every role: nothing may crash (5xx).
 */
class AllPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return array_map(fn ($role) => [$role], [
            'super_admin' => User::ROLE_SUPER_ADMIN,
            'hr_admin' => User::ROLE_HR_ADMIN,
            'manager' => User::ROLE_MANAGER,
            'technician' => User::ROLE_TECHNICIAN,
            'employee' => User::ROLE_EMPLOYEE,
        ]);
    }

    #[DataProvider('roles')]
    public function test_no_page_crashes_for_role(string $role): void
    {
        LeaveType::factory()->create();
        $user = User::factory()->create(['role' => $role]);

        if ($role !== User::ROLE_SUPER_ADMIN) {
            $employee = Employee::factory()->create([
                'user_id' => $user->id,
                'department_id' => Department::factory(),
                'office_id' => Office::factory(),
            ]);
            $employee->shifts()->create(['shift_id' => Shift::factory()->create()->id, 'effective_date' => now()->subMonth()]);
            EmployeeSalary::factory()->create(['employee_id' => $employee->id]);
        }

        $crashed = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || ! in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{')
                || str_starts_with($route->uri(), '_') || str_starts_with($route->uri(), 'storage')) {
                continue;
            }

            $status = $this->actingAs($user)->get('/'.ltrim($route->uri(), '/'))->getStatusCode();

            if ($status >= 500) {
                $crashed[] = "{$name} ({$status})";
            }
        }

        $this->assertSame([], $crashed, "Pages crashing for {$role}: ".implode(', ', $crashed));
    }
}
