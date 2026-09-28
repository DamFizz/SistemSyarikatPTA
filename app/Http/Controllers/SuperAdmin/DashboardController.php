<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('superadmin.dashboard', [
            'totalUsers' => User::count(),
            'totalEmployees' => Employee::count(),
            'totalDepartments' => Department::count(),
            'totalOffices' => Office::count(),
        ]);
    }
}
