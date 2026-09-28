<x-app-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Technician Dashboard</h2>
        <p class="text-sm text-slate-500 mt-1">Welcome back, {{ Auth::user()->name }}.</p>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card label="My Open Tickets" :value="$assignedOpen" />
        <x-stat-card label="Resolved Today" :value="$resolvedToday" />
        <x-stat-card label="Unassigned Tickets" :value="$unassigned" />
    </div>

    <div class="mt-6 bg-white rounded-xl border border-slate-200 p-6 text-sm text-slate-500">
        Your ticket queue will appear here once the Helpdesk module is built.
    </div>
</x-app-layout>
