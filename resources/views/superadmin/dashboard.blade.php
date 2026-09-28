<x-app-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Super Admin Dashboard</h2>
        <p class="text-sm text-slate-500 mt-1">System-wide overview and configuration.</p>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card label="Total Users" :value="$totalUsers" />
        <x-stat-card label="Total Employees" :value="$totalEmployees" />
        <x-stat-card label="Departments" :value="$totalDepartments" />
        <x-stat-card label="Office Locations" :value="$totalOffices" />
    </div>

    <div class="mt-6 bg-white rounded-xl border border-slate-200 p-6 text-sm text-slate-500">
        System configuration, office/geofence management and audit log modules will appear here in the next development phases.
    </div>
</x-app-layout>
