<x-app-layout title="Employees">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="page-title">Employee Management</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $employees->total() }} employee(s) total.</p>
            </div>
            <a href="{{ route('hr.employees.create') }}" class="btn-primary">
                + Add Employee
            </a>
        </div>
    </x-slot>

    <form method="GET" class="filter-bar">
        <div>
            <label class="block text-xs text-slate-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or employee code"
                   class="input">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Department</label>
            <select name="department_id" class="input">
                <option value="">All</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Status</label>
            <select name="status" class="input">
                <option value="">All</option>
                @foreach (['active', 'probation', 'resigned', 'terminated', 'suspended'] as $status)
                    <option value="{{ $status }}" @selected(request('status') == $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-dark">Filter</button>
        @if (request()->hasAny(['search', 'department_id', 'status']))
            <a href="{{ route('hr.employees.index') }}" class="text-sm text-slate-500 underline">Reset</a>
        @endif
    </form>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Position</th>
                    <th class="px-4 py-3">Office</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <x-avatar :user="$employee->user" size="h-9 w-9" />
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-800">{{ $employee->full_name }}</div>
                                    <div class="text-xs text-slate-400">{{ $employee->employee_code }} &middot; {{ $employee->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $employee->department->name }}</td>
                        <td class="px-4 py-3">{{ $employee->position }}</td>
                        <td class="px-4 py-3">{{ $employee->office->name }}</td>
                        <td class="px-4 py-3">
                            <x-status-badge :status="$employee->employment_status" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('hr.employees.edit', $employee) }}" class="btn-secondary btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $employees->links() }}
    </div>
</x-app-layout>
