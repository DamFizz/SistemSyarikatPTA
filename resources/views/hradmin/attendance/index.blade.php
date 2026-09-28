<x-app-layout title="Attendance">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Attendance Records</h2>
        <p class="text-sm text-slate-500 mt-1">{{ request('date', today()->format('Y-m-d')) }}</p>
    </x-slot>

    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-slate-500 mb-1">Date</label>
            <input type="date" name="date" value="{{ request('date', today()->format('Y-m-d')) }}" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Department</label>
            <select name="department_id" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Status</label>
            <select name="status" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All</option>
                @foreach (['present', 'late', 'absent', 'half_day', 'on_leave'] as $status)
                    <option value="{{ $status }}" @selected(request('status') == $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white text-sm rounded-md hover:bg-slate-700">Filter</button>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Clock In</th>
                    <th class="px-4 py-3">Clock Out</th>
                    <th class="px-4 py-3">Distance</th>
                    <th class="px-4 py-3">Selfie</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Flag</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $record->employee->full_name }}</div>
                            <div class="text-xs text-slate-400">{{ $record->employee->department->name }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $record->clock_in_time?->format('H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $record->clock_out_time?->format('H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $record->clock_in_distance_meters }}m</td>
                        <td class="px-4 py-3">
                            @if ($record->selfie_path)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($record->selfie_path) }}" target="_blank" class="text-emerald-600 underline">View</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-status-badge :status="$record->status" /></td>
                        <td class="px-4 py-3">
                            @if ($record->is_flagged)
                                <span class="text-red-600 text-xs font-semibold">FLAGGED</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">No attendance records for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $records->links() }}
    </div>
</x-app-layout>
