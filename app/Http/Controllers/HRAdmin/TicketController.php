<?php

namespace App\Http\Controllers\HRAdmin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = Ticket::with(['employee.department', 'category', 'technician'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('hradmin.tickets.index', compact('tickets'));
    }

    public function show(Ticket $ticket): View
    {
        return view('hradmin.tickets.show', ['ticket' => $ticket->load('messages.sender', 'category', 'employee.department', 'technician')]);
    }
}
