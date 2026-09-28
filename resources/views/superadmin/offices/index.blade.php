<x-app-layout title="Office Locations">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-800">Office Locations / Branches</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $offices->total() }} branch(es).</p>
            </div>
            <a href="{{ route('super-admin.offices.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                + Add Branch
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Address</th>
                    <th class="px-4 py-3">Coordinates</th>
                    <th class="px-4 py-3">Radius</th>
                    <th class="px-4 py-3">NFC Tag</th>
                    <th class="px-4 py-3">Employees</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($offices as $office)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $office->name }}</td>
                        <td class="px-4 py-3 max-w-xs truncate">{{ $office->address }}</td>
                        <td class="px-4 py-3 text-xs">{{ $office->latitude }}, {{ $office->longitude }}</td>
                        <td class="px-4 py-3">{{ $office->allowed_radius_meters }}m</td>
                        <td class="px-4 py-3">
                            @if ($office->nfc_tag_id)
                                <span class="text-emerald-600 text-xs">Configured</span>
                            @else
                                <span class="text-slate-400 text-xs">Not set</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $office->employees_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('super-admin.offices.edit', $office) }}" class="text-emerald-600 hover:text-emerald-800 font-medium">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">No offices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $offices->links() }}</div>
</x-app-layout>
