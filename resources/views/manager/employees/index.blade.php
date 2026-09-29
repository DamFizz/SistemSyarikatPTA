<x-app-layout title="My Team">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="page-title">My Team</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $team->total() }} team member(s).</p>
            </div>
            <a href="{{ route('manager.employees.create') }}" class="btn-primary">
                + Add Team Member
            </a>
        </div>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Position</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($team as $employee)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $employee->full_name }}</div>
                            <div class="text-xs text-slate-400">{{ $employee->employee_code }} &middot; {{ $employee->user->email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $employee->position }}</td>
                        <td class="px-4 py-3">{{ str($employee->employment_type)->replace('_', ' ')->title() }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$employee->employment_status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400">No team members yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $team->links() }}</div>
</x-app-layout>
