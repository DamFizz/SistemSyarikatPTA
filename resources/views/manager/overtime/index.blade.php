<x-app-layout title="Overtime Approvals">
    <x-slot name="header">
        <p class="eyebrow">My Team</p>
        <h2 class="page-title mt-1">Overtime Approvals</h2>
        <p class="muted mt-1">Requests from employees you manage. Monthly overtime limits are checked when you approve.</p>
    </x-slot>

    <x-status-tabs />

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Hours</th>
                    <th>OT this month</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($overtimes as $ot)
                    @php
                        $capApplies = $workHours->otCapApplies($ot->employee);
                        $used = $workHours->otHoursInMonth($ot->employee, $ot->date, ['approved', 'paid']);
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $ot->employee->full_name }}</div>
                            <div class="text-xs text-slate-400">{{ $ot->employee->department?->name }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $ot->date->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ substr($ot->start_time, 0, 5) }}–{{ substr($ot->end_time, 0, 5) }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $ot->total_hours }}h</td>
                        <td class="px-4 py-3">
                            @if ($capApplies)
                                <span @class(['tabular-nums', 'font-semibold text-rose-600' => $used + $ot->total_hours > $workHours->otMonthlyCap()])>
                                    {{ rtrim(rtrim(number_format($used, 2), '0'), '.') }}h / {{ $workHours->otMonthlyCap() }}h approved
                                </span>
                            @else
                                <span class="chip bg-slate-100 text-slate-600">Exempt</span>
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-request-details :request="$ot" type="overtime" /></td>
                        <td class="px-4 py-3"><x-status-badge :status="$ot->status" /></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-1.5">
                            @if ($ot->status === 'pending')
                                <form method="POST" action="{{ route('manager.overtime.approve', $ot) }}" class="inline">
                                    @csrf
                                    <button class="btn-success-soft btn-sm">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('manager.overtime.reject', $ot) }}" class="inline">
                                    @csrf
                                    <button class="btn-danger-soft btn-sm">Reject</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-400">No overtime requests here.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $overtimes->links() }}</div>
</x-app-layout>
