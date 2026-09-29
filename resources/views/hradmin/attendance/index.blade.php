<x-app-layout title="Attendance Records">
    <x-slot name="header">
        <p class="eyebrow">Operations</p>
        <h2 class="page-title mt-1">Attendance Records</h2>
        <p class="muted mt-1">
            @if (request()->boolean('flagged') && ! request()->filled('date'))
                All flagged records
            @else
                {{ \Illuminate\Support\Carbon::parse(request('date', today()->format('Y-m-d')))->format('l, d F Y') }}
            @endif
        </p>
    </x-slot>

    <form method="GET" class="filter-bar">
        <div>
            <label class="mb-1 block">Date</label>
            <input type="date" name="date" value="{{ request('date', request()->boolean('flagged') ? '' : today()->format('Y-m-d')) }}" class="input">
        </div>
        <div>
            <label class="mb-1 block">Department</label>
            <select name="department_id" class="input">
                <option value="">All</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block">Status</label>
            <select name="status" class="input">
                <option value="">All</option>
                @foreach (['present', 'late', 'absent', 'half_day', 'on_leave'] as $status)
                    <option value="{{ $status }}" @selected(request('status') == $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 self-center pt-5 !normal-case !tracking-normal !text-sm !font-medium !text-slate-600">
            <input type="checkbox" name="flagged" value="1" @checked(request()->boolean('flagged'))> Flagged only
        </label>
        <button type="submit" class="btn-dark">Filter</button>
        @if (request()->hasAny(['date', 'department_id', 'status', 'flagged']))
            <a href="{{ route('hr.attendance.index') }}" class="btn-ghost">Reset</a>
        @endif
    </form>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Clock in</th>
                    <th>Clock out</th>
                    <th>Verified by</th>
                    <th>Selfies</th>
                    <th>Status</th>
                    <th>Review</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr @class(['bg-rose-50/40' => $record->is_flagged])>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $record->employee->full_name }}</div>
                            <div class="text-xs text-slate-400">{{ $record->employee->department->name }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $record->attendance_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-mono text-slate-900">{{ $record->clock_in_time?->format('H:i') ?? '–' }}</div>
                            @if ($record->clock_in_distance_meters !== null)
                                <div class="text-xs text-slate-400">{{ $record->clock_in_distance_meters }}m{{ $record->clock_in_accuracy_meters ? ' · ±'.$record->clock_in_accuracy_meters.'m' : '' }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-mono text-slate-900">{{ $record->clock_out_time?->format('H:i') ?? '–' }}</div>
                            @if ($record->clock_out_distance_meters !== null)
                                <div class="text-xs text-slate-400">{{ $record->clock_out_distance_meters }}m</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @switch($record->verification_method)
                                @case('wifi')
                                    <span class="chip bg-emerald-50 text-emerald-700"><x-icon name="wifi" class="h-3.5 w-3.5" /> Office WiFi</span>
                                    @break
                                @case('testing')
                                    <span class="chip bg-amber-50 text-amber-700"><x-icon name="warning" class="h-3.5 w-3.5" /> Testing</span>
                                    @break
                                @case(null)
                                    <span class="text-slate-300">–</span>
                                    @break
                                @default
                                    <span class="chip bg-slate-100 text-slate-600 uppercase">{{ $record->verification_method }}</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex -space-x-2">
                                @foreach (['in' => $record->selfie_path, 'out' => $record->clock_out_selfie_path] as $type => $path)
                                    @if ($path)
                                        <a href="{{ route('attendance.selfie', [$record, $type]) }}" target="_blank" title="Clock-{{ $type }} selfie" class="block h-9 w-9 overflow-hidden rounded-xl ring-2 ring-white transition hover:z-10 hover:scale-110">
                                            <img src="{{ route('attendance.selfie', [$record, $type]) }}" alt="Clock-{{ $type }} selfie" loading="lazy" class="h-full w-full object-cover">
                                        </a>
                                    @endif
                                @endforeach
                                @if (! $record->selfie_path && ! $record->clock_out_selfie_path)
                                    <span class="text-slate-300">–</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3"><x-status-badge :status="$record->status" /></td>
                        <td class="px-4 py-3 min-w-[14rem] max-w-[18rem] !whitespace-normal">
                            @if ($record->is_flagged)
                                <div class="flex items-start gap-1.5 text-xs text-rose-600">
                                    <x-icon name="flag" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                    <span>{{ implode(' · ', $record->flag_reasons ?? ['Flagged']) }}</span>
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs text-emerald-600"><x-icon name="check" class="h-3.5 w-3.5" /> Clean</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-400">No attendance records for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $records->links() }}
    </div>
</x-app-layout>
