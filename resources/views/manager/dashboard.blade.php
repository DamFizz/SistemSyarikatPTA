<x-app-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Manager Dashboard</h2>
        <p class="text-sm text-slate-500 mt-1">Welcome back, {{ Auth::user()->name }}.</p>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-stat-card label="Team Size" :value="$teamSize" />
        <x-stat-card label="Pending Leave Approvals" :value="$pendingLeave" />
        <x-stat-card label="Pending OT Approvals" :value="$pendingOvertime" />
    </div>

    <div class="mt-6 bg-white rounded-xl border border-slate-200 p-6 text-sm text-slate-500">
        Department attendance, task assignment and announcement tools will appear here in the next development phases.
    </div>
</x-app-layout>
