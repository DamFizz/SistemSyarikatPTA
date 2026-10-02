<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_uploads_a_photo_that_shows_across_the_app(): void
    {
        $employee = Employee::factory()->create(['department_id' => Department::factory(), 'office_id' => Office::factory()]);
        $user = $employee->user;

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Profile photo')->assertSee('Install the SEMS app');

        $this->actingAs($user)->post(route('profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('me.png', 800, 600),
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $user->refresh();
        $this->assertStringStartsWith(StoredFile::PREFIX, $user->avatar);
        $this->assertNotNull($user->avatarUrl());

        // Visible in the shell (top bar / menus) and to colleagues.
        $this->actingAs($user)->get(route('employee.dashboard'))->assertOk()->assertSee($user->avatarUrl(), false);
        $colleague = User::factory()->create();
        $this->actingAs($colleague)->get($user->avatarUrl())->assertOk()->assertHeader('Cache-Control');

        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);
        $this->actingAs($hr)->get(route('hr.employees.index'))->assertOk()->assertSee($user->avatarUrl(), false);
    }

    public function test_a_new_photo_replaces_the_old_one_and_can_be_removed(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('a.jpg', 300, 300)]);
        $first = $user->fresh()->avatar;
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('b.jpg', 300, 300)]);

        $this->assertNotSame($first, $user->fresh()->avatar);
        $this->assertSame(1, StoredFile::count(), 'The previous photo is deleted.');

        $this->actingAs($user)->delete(route('profile.photo.destroy'))->assertSessionHas('success');
        $this->assertNull($user->fresh()->avatar);
        $this->assertSame(0, StoredFile::count());
    }

    public function test_only_reasonable_images_are_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf')])
            ->assertSessionHasErrors('photo');
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('tiny.png', 20, 20)])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->avatar);
    }

    public function test_photos_need_a_signed_in_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('a.jpg', 200, 200)]);
        $url = $user->fresh()->avatarUrl();

        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));
    }
}
