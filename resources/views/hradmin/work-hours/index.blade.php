@php
    $fmt = fn ($h) => rtrim(rtrim(number_format((float) $h, 1, '.', ''), '0'), '.');
    $pendingCount = $justifications->where('status', 'pending')->count();
@endphp

<x-app-layout title="Working Hours">
    <x-slot name="header">
        <p class="eyebrow">Operations · Employment Act</p>
        <h2 class="page-title mt-1">Working Hours Compliance</h2>
        <p class="muted mt-1">Monthly overtime cap, weekly hours limit and the weekly rest day.</p>
    </x-slot>

    <x-validation-alert />

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Limits --}}
        <form method="POST" action="{{ route('hr.work-hours.settings') }}" class="form-card !p-6 lg:col-span-1 lg:self-start">
            @csrf
            @method('PUT')
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><x-icon name="shield" /></span>
                <div>
                    <h3 class="card-title">Limits</h3>
                    <p class="text-xs text-slate-500">Applied to every clock-in and overtime request.</p>
                </div>
            </div>

            <div>
                <x-input-label for="ot_monthly_cap_hours" value="Monthly overtime cap (hours)" />
                <x-text-input id="ot_monthly_cap_hours" name="ot_monthly_cap_hours" type="number" step="0.5" min="1" class="mt-1.5 block w-full" :value="old('ot_monthly_cap_hours', $fmt($settings['ot_monthly_cap_hours']))" required />
            </div>
            <div>
                <x-input-label for="ot_cap_salary_threshold" value="Cap applies automatically at or below (RM / month)" />
                <x-text-input id="ot_cap_salary_threshold" name="ot_cap_salary_threshold" type="number" step="1" min="0" class="mt-1.5 block w-full" :value="old('ot_cap_salary_threshold', (int) $settings['ot_cap_salary_threshold'])" required />
                <p class="mt-1.5 text-xs text-slate-400">Employees set to “Auto” are capped when their basic salary is at or below this amount.</p>
            </div>
            <div>
                <x-input-label for="weekly_hours_limit" value="Weekly working hours limit" />
                <x-text-input id="weekly_hours_limit" name="weekly_hours_limit" type="number" step="0.5" min="1" class="mt-1.5 block w-full" :value="old('weekly_hours_limit', $fmt($settings['weekly_hours_limit']))" required />
                <p class="mt-1.5 text-xs text-slate-400">Clock-in is blocked once an employee reaches this many hours in a Monday–Sunday week.</p>
            </div>
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                <input type="hidden" name="rest_day_enforced" value="0">
                <input type="checkbox" name="rest_day_enforced" value="1" class="mt-0.5" @checked($settings['rest_day_enforced'])>
                <div>
                    <div class="text-sm font-semibold text-slate-900">Enforce weekly rest day</div>
                    <div class="mt-0.5 text-xs leading-relaxed text-slate-500">After {{ \App\Services\WorkHoursService::MAX_CONSECUTIVE_DAYS }} days in a row, employees are warned and must justify working on the 7th day.</div>
                </div>
            </label>
            <button type="submit" class="btn-primary w-full">Save limits</button>
        </form>

        {{-- Rest-day justifications --}}
        <div class="card lg:col-span-2">
            <div class="card-header">
                <div class="flex items-center gap-2">
                    <x-icon name="calendar" class="h-[18px] w-[18px] text-amber-500" />
                    <h3 class="card-title">Rest-day justifications</h3>
                </div>
                @if ($pendingCount)
                    <span class="chip bg-amber-50 text-amber-700 ring-1 ring-amber-600/15">{{ $pendingCount }} to review</span>
                @endif
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($justifications as $item)
                    <li class="px-5 py-4" x-data="{ open: false }">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="font-semibold text-slate-900">{{ $item->employee->full_name }}</span>
                                    <span class="text-xs text-slate-400">{{ $item->employee->department?->name }} · {{ $item->work_date->format('D, d M Y') }} · day {{ $item->consecutive_days }} in a row</span>
                                </div>
                                <p class="mt-1.5 text-sm text-slate-600">“{{ $item->reason }}”</p>
                                @if ($item->status !== 'pending')
                                    <p class="mt-1.5 text-xs text-slate-400">
                                        {{ ucfirst($item->status) }} by {{ $item->reviewer?->name ?? '—' }} · {{ $item->reviewed_at?->diffForHumans() }}
                                        @if ($item->review_note) — {{ $item->review_note }} @endif
                                    </p>
                                @endif
                            </div>
                            @if ($item->status === 'pending')
                                <button type="button" @click="open = !open" class="btn-secondary btn-sm shrink-0">Review</button>
                            @else
                                <x-status-badge :status="$item->status === 'acknowledged' ? 'approved' : 'rejected'" />
                            @endif
                        </div>
                        @if ($item->status === 'pending')
                            <form x-show="open" x-cloak method="POST" action="{{ route('hr.work-hours.review', $item) }}" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                                @csrf
                                <input type="text" name="review_note" maxlength="1000" placeholder="Note (optional)" class="input flex-1">
                                <div class="flex gap-2">
                                    <button name="status" value="acknowledged" class="btn-success-soft btn-sm flex-1 sm:flex-none">Acknowledge</button>
                                    <button name="status" value="rejected" class="btn-danger-soft btn-sm flex-1 sm:flex-none">Reject</button>
                                </div>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="flex flex-col items-center gap-2 px-5 py-12 text-center text-sm text-slate-400">
                        <x-icon name="check-circle" class="h-8 w-8 text-emerald-400" />
                        No one has worked through their rest day.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Employee overview --}}
    <div class="mt-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h3 class="text-base font-semibold">Employees</h3>
            <p class="muted">Choose who the monthly overtime cap applies to, by salary tier or job category.</p>
        </div>
        <form method="GET" class="flex gap-2">
            <select name="department_id" class="input" onchange="this.form.submit()">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="card mt-4 overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Basic salary</th>
                    <th>Overtime cap</th>
                    <th>OT · {{ now()->format('M') }}</th>
                    <th>This week</th>
                    <th>Days in a row</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $employee = $row['employee'];
                        $summary = $row['summary'];
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $employee->full_name }}</div>
                            <div class="text-xs text-slate-400">{{ $employee->position }} · {{ $employee->department?->name }}</div>
                        </td>
                        <td class="px-4 py-3 tabular-nums">{{ $row['salary'] !== null ? 'RM '.number_format($row['salary'], 2) : '—' }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('hr.work-hours.employee', $employee) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <select name="ot_cap_mode" class="input py-1.5 text-xs" onchange="this.form.submit()">
                                    @foreach (\App\Services\WorkHoursService::CAP_MODES as $value => $label)
                                        <option value="{{ $value }}" @selected(($employee->ot_cap_mode ?? 'auto') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if ($summary['ot_cap_applies'])
                                    <span class="chip bg-emerald-50 text-emerald-700">Capped</span>
                                @else
                                    <span class="chip bg-slate-100 text-slate-500">Not capped</span>
                                @endif
                            </form>
                        </td>
                        <td class="px-4 py-3 tabular-nums">
                            <span @class(['font-semibold text-rose-600' => $summary['ot_cap_applies'] && $summary['ot_used'] >= $summary['ot_cap']])>{{ $fmt($summary['ot_used']) }}h</span>
                            @if ($summary['ot_cap_applies'])
                                <span class="text-slate-400">/ {{ $fmt($summary['ot_cap']) }}h</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 tabular-nums">
                            <span @class(['font-semibold text-rose-600' => $summary['week_hours'] >= $summary['week_limit'], 'font-semibold text-amber-600' => $summary['week_hours'] >= $summary['week_limit'] * 0.85 && $summary['week_hours'] < $summary['week_limit']])>{{ $fmt($summary['week_hours']) }}h</span>
                            <span class="text-slate-400">/ {{ $fmt($summary['week_limit']) }}h</span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($summary['rest_day_required'] || $summary['consecutive_days'] > \App\Services\WorkHoursService::MAX_CONSECUTIVE_DAYS)
                                <span class="chip bg-rose-50 text-rose-700">{{ $summary['consecutive_days'] }} · rest day due</span>
                            @elseif ($summary['consecutive_days'] >= 5)
                                <span class="chip bg-amber-50 text-amber-700">{{ $summary['consecutive_days'] }}</span>
                            @else
                                <span class="text-slate-500">{{ $summary['consecutive_days'] }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400">No active employees.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $employees->links() }}</div>
</x-app-layout>
