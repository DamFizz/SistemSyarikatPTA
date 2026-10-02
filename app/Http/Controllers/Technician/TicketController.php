<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = Ticket::with(['employee', 'category'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when(! $request->filled('status'), fn ($q) => $q->whereNotIn('status', ['resolved', 'closed']))
            ->when($request->filled('mine'), fn ($q) => $q->where('assigned_technician_id', Auth::id()))
            ->orderByRaw("case priority when 'critical' then 0 when 'high' then 1 when 'medium' then 2 else 3 end")
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('technician.tickets.index', compact('tickets'));
    }

    public function show(Ticket $ticket): View
    {
        return view('technician.tickets.show', ['ticket' => $ticket->load('messages.sender', 'category', 'employee.department')]);
    }

    public function assign(Ticket $ticket): RedirectResponse
    {
        $ticket->update(['assigned_technician_id' => Auth::id(), 'status' => Ticket::STATUS_ASSIGNED]);

        AuditLog::record('assign', 'ticket', "Ticket {$ticket->ticket_code} assigned to ".Auth::user()->name);

        return back()->with('success', 'Ticket assigned to you.');
    }

    public function updateStatus(Ticket $ticket): RedirectResponse
    {
        $data = request()->validate(['status' => ['required', 'in:in_progress,waiting_user,resolved,closed']]);

        $ticket->update([
            'status' => $data['status'],
            'resolved_at' => in_array($data['status'], ['resolved', 'closed']) ? now() : $ticket->resolved_at,
        ]);

        AuditLog::record('update_status', 'ticket', "Ticket {$ticket->ticket_code} status changed to {$data['status']}");

        return back()->with('success', 'Ticket status updated.');
    }

    public function reply(Ticket $ticket): RedirectResponse
    {
        $data = request()->validate(['message' => ['required', 'string', 'max:2000']]);

        $ticket->messages()->create(['sender_id' => Auth::id(), 'message' => $data['message']]);

        if ($ticket->status === Ticket::STATUS_ASSIGNED) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        return back()->with('success', 'Reply sent.');
    }
}
