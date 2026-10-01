<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public diagnostic: shows the caller only their own IP as SEMS resolves it behind
 * Railway/Fastly, plus the deployed commit — used to verify the office WiFi check.
 */
class NetworkProbeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'resolved_ip' => $request->ip(),
            'ip_version' => str_contains((string) $request->ip(), ':') ? 6 : 4,
            'x_forwarded_for' => $request->headers->get('X-Forwarded-For'),
            'x_real_ip' => $request->headers->get('X-Real-IP'),
            'fastly_client_ip' => $request->headers->get('Fastly-Client-IP'),
            'connecting_proxy' => $request->server->get('REMOTE_ADDR'),
            'build' => substr((string) env('RAILWAY_GIT_COMMIT_SHA', 'local'), 0, 7),
        ])->header('Cache-Control', 'no-store');
    }
}
