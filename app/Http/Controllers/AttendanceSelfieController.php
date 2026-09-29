<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceSelfieController extends Controller
{
    /**
     * Serve a selfie only to the employee themselves, their manager, or HR / super admin.
     */
    public function show(Request $request, Attendance $attendance, string $type): StreamedResponse
    {
        $user = $request->user();
        $attendance->loadMissing('employee');

        $allowed = $user->hasRole('hr_admin', 'super_admin')
            || $attendance->employee->user_id === $user->id
            || ($user->isManager() && $user->employee && $attendance->employee->manager_id === $user->employee->id);

        abort_unless($allowed, 403);

        $path = $type === 'out' ? $attendance->clock_out_selfie_path : $attendance->selfie_path;
        abort_unless($path, 404);

        // Older records were stored on the public disk before selfies became private.
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->response($path, headers: [
                    'Cache-Control' => 'private, max-age=600',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404);
    }
}
