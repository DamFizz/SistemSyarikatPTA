<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\ShiftReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view. If this device signed in before, show that
     * employee's clock-in time and countdown beside the form.
     */
    public function create(Request $request, ShiftReminderService $reminders): View
    {
        $userId = $request->cookie(ShiftReminderService::DEVICE_COOKIE);
        $user = is_numeric($userId) ? User::find((int) $userId) : null;

        return view('auth.login', ['reminder' => $reminders->forUser($user)]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Remember (encrypted, http-only) which employee uses this device for the login-page reminder.
        if ($request->user()->employee) {
            Cookie::queue(Cookie::forever(ShiftReminderService::DEVICE_COOKIE, (string) $request->user()->id));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * "Not you?" — stop showing the reminder on this device.
     */
    public function forgetDevice(): RedirectResponse
    {
        Cookie::queue(Cookie::forget(ShiftReminderService::DEVICE_COOKIE));

        return redirect()->route('login');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
