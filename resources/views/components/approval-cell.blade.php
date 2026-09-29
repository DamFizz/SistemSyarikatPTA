@props(['request', 'approveRoute', 'rejectRoute'])

{{-- HR sees who is responsible; only the employee's manager approves.
     Super Admin steps in only when no manager can approve the request. --}}
@if ($request->status === 'pending')
    @php $approver = $request->employee->approvingManager(); @endphp
    @if ($approver)
        <span class="text-xs text-slate-500">Awaiting <span class="font-medium text-slate-700">{{ $approver->full_name }}</span></span>
    @elseif (auth()->user()->isSuperAdmin())
        <form method="POST" action="{{ route($approveRoute, $request) }}" class="inline">
            @csrf
            <button class="btn-success-soft btn-sm" title="No manager assigned — approving as Super Admin">Approve</button>
        </form>
        <form method="POST" action="{{ route($rejectRoute, $request) }}" class="inline">
            @csrf
            <button class="btn-danger-soft btn-sm">Reject</button>
        </form>
    @else
        <span class="chip bg-rose-50 text-rose-700 ring-1 ring-rose-600/15" title="Assign a manager to this employee or their department">No manager assigned</span>
    @endif
@else
    <span class="text-xs text-slate-400">by {{ $request->approver?->name ?? '—' }}</span>
@endif
