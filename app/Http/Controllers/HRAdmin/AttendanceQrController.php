<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceQrToken;
use App\Models\Office;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttendanceQrController extends Controller
{
    public const ROTATE_SECONDS = 60;

    public function display(Office $office): View
    {
        return view('hradmin.attendance.qr-display', ['office' => $office, 'rotateSeconds' => self::ROTATE_SECONDS]);
    }

    public function current(Office $office): JsonResponse
    {
        $token = DB::transaction(function () use ($office) {
            $existing = AttendanceQrToken::where('office_id', $office->id)
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            return AttendanceQrToken::create([
                'office_id' => $office->id,
                'checkpoint_name' => $office->name.' Entrance',
                'token' => Str::random(40),
                'expires_at' => now()->addSeconds(self::ROTATE_SECONDS),
            ]);
        });

        return response()->json([
            'token' => $token->token,
            'expires_at' => $token->expires_at->toIso8601String(),
            'seconds_remaining' => max(0, now()->diffInSeconds($token->expires_at, false)),
            'svg' => QrCodeService::svg($token->token, 260),
        ]);
    }
}
