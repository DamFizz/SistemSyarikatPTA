<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\StoredFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $departmentId = Auth::user()->employee?->department_id;

        $announcements = Announcement::with(['creator', 'department'])
            ->where(function ($query) use ($departmentId) {
                $query->whereNull('department_id')->orWhere('department_id', $departmentId);
            })
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->paginate(10);

        // Opening the list counts as reading everything, which clears the banner and the bell dot.
        Auth::user()->forceFill(['announcements_seen_at' => now()])->save();

        return view('announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        abort_unless(Auth::user()->hasRole('super_admin', 'hr_admin', 'manager'), 403);

        return view('announcements.create', ['departments' => Department::orderBy('name')->get()]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = StoredFile::storeUpload($request->file('attachment'));
        }

        $announcement = Announcement::create([
            ...$data,
            'attachment' => $attachmentPath,
            'created_by' => Auth::id(),
        ]);

        AuditLog::record('create', 'announcement', "Created announcement \"{$announcement->title}\"");

        return redirect()->route('announcements.index')->with('success', 'Announcement published.');
    }

    public function dismiss(): RedirectResponse
    {
        Auth::user()->forceFill(['announcements_seen_at' => now()])->save();

        return back();
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->canBeDeletedBy(Auth::user()), 403, 'You can only delete announcements you created.');

        $this->deleteAnnouncement($announcement);

        return back()->with('success', 'Announcement deleted.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer'],
        ]);

        $announcements = Announcement::whereKey($data['ids'])->get();
        $deletable = $announcements->filter(fn (Announcement $announcement) => $announcement->canBeDeletedBy(Auth::user()));

        abort_if($deletable->isEmpty(), 403, 'You cannot delete the selected announcements.');

        $deletable->each(fn (Announcement $announcement) => $this->deleteAnnouncement($announcement));

        $message = $deletable->count().' announcement(s) deleted.';
        $skipped = $announcements->count() - $deletable->count();

        if ($skipped > 0) {
            $message .= " {$skipped} skipped because you can only delete your own.";
        }

        return back()->with('success', $message);
    }

    private function deleteAnnouncement(Announcement $announcement): void
    {
        StoredFile::deleteReference($announcement->attachment);

        $announcement->delete();

        AuditLog::record('delete', 'announcement', "Deleted announcement \"{$announcement->title}\"");
    }
}
