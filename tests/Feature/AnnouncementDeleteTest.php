<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function announcement(User $author, array $attributes = []): Announcement
    {
        return Announcement::create([
            'title' => 'Notice '.fake()->unique()->word(),
            'description' => 'Details',
            'priority' => 'normal',
            'created_by' => $author->id,
            ...$attributes,
        ]);
    }

    public function test_hr_admin_can_delete_any_announcement_and_its_attachment(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->create('memo.pdf', 10)->store('attachments/announcements', 'public');
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);
        $announcement = $this->announcement($manager, ['attachment' => $path]);

        $this->actingAs($hr)->delete(route('announcements.destroy', $announcement))->assertRedirect();

        $this->assertModelMissing($announcement);
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'delete', 'module' => 'announcement']);
    }

    public function test_manager_can_only_delete_own_announcements(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);
        $own = $this->announcement($manager);
        $others = $this->announcement($hr);

        $this->actingAs($manager)->delete(route('announcements.destroy', $others))->assertForbidden();
        $this->actingAs($manager)->delete(route('announcements.destroy', $own))->assertRedirect();

        $this->assertModelExists($others);
        $this->assertModelMissing($own);
    }

    public function test_employee_cannot_delete(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);
        $employee = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $announcement = $this->announcement($hr);

        $this->actingAs($employee)->delete(route('announcements.destroy', $announcement))->assertForbidden();
        $this->actingAs($employee)->delete(route('announcements.bulk-destroy'), ['ids' => [$announcement->id]])->assertForbidden();
        $this->actingAs($employee)->get(route('announcements.index'))->assertOk()->assertDontSee('Delete selected');

        $this->assertModelExists($announcement);
    }

    public function test_bulk_delete_removes_selected_and_skips_forbidden_ones(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);
        $mine = [$this->announcement($manager), $this->announcement($manager)];
        $notMine = $this->announcement($hr);
        $untouched = $this->announcement($manager);

        $this->actingAs($manager)->get(route('announcements.index'))->assertOk()->assertSee('Delete selected');

        $this->actingAs($manager)
            ->delete(route('announcements.bulk-destroy'), ['ids' => [$mine[0]->id, $mine[1]->id, $notMine->id]])
            ->assertRedirect()
            ->assertSessionHas('success', '2 announcement(s) deleted. 1 skipped because you can only delete your own.');

        $this->assertModelMissing($mine[0]);
        $this->assertModelMissing($mine[1]);
        $this->assertModelExists($notMine);
        $this->assertModelExists($untouched);
    }

    public function test_bulk_delete_requires_selection(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR_ADMIN]);

        $this->actingAs($hr)->delete(route('announcements.bulk-destroy'), ['ids' => []])->assertSessionHasErrors('ids');
    }
}
