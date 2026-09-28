<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreTicketRequest;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403);

        return view('employee.tickets.index', [
            'tickets' => $employee->tickets()->with('category')->orderByDesc('id')->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('employee.tickets.create', ['categories' => TicketCategory::orderBy('name')->get()]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $employee = Auth::user()->employee;
        abort_if(! $employee, 403);

        $data = $request->validated();

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments/tickets', 'public');
        }

        $ticket = Ticket::create([
            'ticket_code' => 'TCK-'.str_pad((string) (Ticket::max('id') + 1), 5, '0', STR_PAD_LEFT),
            'employee_id' => $employee->id,
            'department_id' => $employee->department_id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'attachment' => $attachmentPath,
            'status' => Ticket::STATUS_OPEN,
        ]);

        AuditLog::record('create', 'ticket', "{$employee->full_name} submitted ticket {$ticket->ticket_code}");

        return redirect()->route('employee.tickets.show', $ticket)->with('success', 'Ticket submitted.');
    }

    public function show(Ticket $ticket): View
    {
        abort_unless($ticket->employee_id === Auth::user()->employee?->id, 403);

        return view('employee.tickets.show', ['ticket' => $ticket->load('messages.sender', 'category', 'technician')]);
    }

    public function reply(Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->employee_id === Auth::user()->employee?->id, 403);

        $data = request()->validate(['message' => ['required', 'string', 'max:2000']]);

        $ticket->messages()->create(['sender_id' => Auth::id(), 'message' => $data['message']]);

        if ($ticket->status === Ticket::STATUS_WAITING_USER) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        return back()->with('success', 'Reply sent.');
    }
}
