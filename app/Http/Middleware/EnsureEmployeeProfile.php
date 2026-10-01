<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Self-service pages (attendance, leave, overtime, payslips, helpdesk) need an employee
 * profile. Accounts without one (e.g. Super Admin) are sent to their dashboard with a
 * clear message instead of a bare 403 — this happens most often when Laravel redirects
 * to an "intended" URL left over from a previous session.
 */
class EnsureEmployeeProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->user()?->employee;

        $problem = match (true) {
            ! $employee => $request->user()?->isSuperAdmin()
                ? 'Super Admin accounts don’t have attendance, leave or payslips. Sign in with an employee account to use those.'
                : 'Your account isn’t linked to an employee profile yet. Please ask HR to set it up.',
            ! $employee->office_id => 'You’re not assigned to an office yet, so attendance isn’t available. Please ask HR.',
            default => null,
        };

        if ($problem === null) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $problem], 403);
        }

        return redirect()->route('dashboard')->with('warning', $problem);
    }
}
