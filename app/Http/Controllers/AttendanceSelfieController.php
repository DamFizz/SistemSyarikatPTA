<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendancePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttendanceSelfieController extends Controller
{
    /**
     * Serve a selfie only to the employee themselves, their manager, or HR / super admin.
     */
    public function show(Request $request, Attendance $attendance, string $type): Response
    {
        $user = $request->user();
        $attendance->loadMissing('employee');

        $allowed = $user->hasRole('hr_admin', 'super_admin')
            || $attendance->employee->user_id === $user->id
            || ($user->isManager() && $attendance->employee->isApprovableBy($user->employee));

        abort_unless($allowed, 403);

        $headers = [
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $path = $attendance->selfiePath($type);
        abort_unless($path, 404);

        if ($path === AttendancePhoto::STORAGE_MARKER) {
            $photo = $attendance->photos()->where('type', $type)->firstOrFail();

            return response($photo->binary(), 200, ['Content-Type' => $photo->mime, ...$headers]);
        }

        // Older records were stored on disk before selfies moved to the database.
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path, headers: $headers);
            }
        }

        abort(404);
    }
}
