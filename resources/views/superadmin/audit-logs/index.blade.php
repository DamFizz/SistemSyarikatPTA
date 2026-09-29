<x-app-layout title="Audit Log">
    <x-slot name="header">
        <h2 class="page-title">Audit Log</h2>
        <p class="text-sm text-slate-500 mt-1">Read-only system activity trail. Records cannot be edited or deleted.</p>
    </x-slot>

    <form method="GET" class="filter-bar">
        <div>
            <label class="block text-xs text-slate-500 mb-1">User</label>
            <select name="user_id" class="input">
                <option value="">All</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Module</label>
            <select name="module" class="input">
                <option value="">All</option>
                @foreach ($modules as $module)
                    <option value="{{ $module }}" @selected(request('module') == $module)>{{ ucfirst($module) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="input">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="input">
        </div>
        <button type="submit" class="btn-dark">Filter</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-modern">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Date/Time</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Module</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$log->action" /></td>
                        <td class="px-4 py-3">{{ ucfirst($log->module) }}</td>
                        <td class="px-4 py-3">{{ $log->description }}</td>
                        <td class="px-4 py-3 text-xs text-slate-400">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No audit log entries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-app-layout>
