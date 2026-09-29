<x-app-layout title="Departments">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="page-title">Departments</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $departments->total() }} department(s).</p>
            </div>
            <a href="{{ route('hr.departments.create') }}" class="btn-primary">
                + Add Department
            </a>
        </div>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Manager</th>
                    <th class="px-4 py-3">Employees</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $department->name }}</td>
                        <td class="px-4 py-3">{{ $department->manager?->full_name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $department->employees_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('hr.departments.edit', $department) }}" class="btn-secondary btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400">No departments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $departments->links() }}
    </div>
</x-app-layout>
