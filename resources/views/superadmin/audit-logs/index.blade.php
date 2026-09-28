<x-app-layout title="Audit Log">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Audit Log</h2>
        <p class="text-sm text-slate-500 mt-1">Read-only system activity trail. Records cannot be edited or deleted.</p>
    </x-slot>

    <form method="GET" class="bg-white rounded-xl border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-slate-500 mb-1">User</label>
            <select name="user_id" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Module</label>
            <select name="module" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All</option>
                @foreach ($modules as $module)
                    <option value="{{ $module }}" @selected(request('module') == $module)>{{ ucfirst($module) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-800 text-white text-sm rounded-md hover:bg-slate-700">Filter</button>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Date/Time</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Module</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
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
