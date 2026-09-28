<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Redirect the authenticated user to their role-specific dashboard.
     */
    public function index(): RedirectResponse
    {
        return match (Auth::user()->role) {
            User::ROLE_SUPER_ADMIN => redirect()->route('super-admin.dashboard'),
            User::ROLE_HR_ADMIN => redirect()->route('hr.dashboard'),
            User::ROLE_MANAGER => redirect()->route('manager.dashboard'),
            User::ROLE_TECHNICIAN => redirect()->route('technician.dashboard'),
            default => redirect()->route('employee.dashboard'),
        };
    }
}
