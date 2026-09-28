<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();

        return view('technician.dashboard', [
            'assignedOpen' => Ticket::where('assigned_technician_id', $userId)
                ->whereNotIn('status', ['resolved', 'closed'])->count(),
            'resolvedToday' => Ticket::where('assigned_technician_id', $userId)
                ->whereDate('resolved_at', today())->count(),
            'unassigned' => Ticket::where('status', Ticket::STATUS_OPEN)->whereNull('assigned_technician_id')->count(),
        ]);
    }
}
