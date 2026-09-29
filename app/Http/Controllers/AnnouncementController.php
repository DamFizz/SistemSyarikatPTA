<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
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
            $attachmentPath = $request->file('attachment')->store('attachments/announcements', 'public');
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
}
