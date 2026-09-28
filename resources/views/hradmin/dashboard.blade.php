<x-app-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">HR / Admin Dashboard</h2>
        <p class="text-sm text-slate-500 mt-1">Welcome back, {{ Auth::user()->name }}.</p>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card label="Active Employees" :value="$totalEmployees" />
        <x-stat-card label="Pending Leave" :value="$pendingLeave" />
        <x-stat-card label="Pending Overtime" :value="$pendingOvertime" />
        <x-stat-card label="Open Helpdesk Tickets" :value="$openTickets" />
    </div>

    <div class="mt-6 bg-white rounded-xl border border-slate-200 p-6 text-sm text-slate-500">
        Attendance trend, payroll status and department reports will appear here once the relevant modules are built.
    </div>
</x-app-layout>
