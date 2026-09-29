<x-app-layout title="Dashboard">
    <x-slot name="header">
        <p class="eyebrow">{{ now()->format('l, d F Y') }}</p>
        <h2 class="page-title mt-1">Support Desk</h2>
        <p class="muted mt-1">Welcome back, {{ Auth::user()->name }}.</p>
    </x-slot>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <x-stat-card label="My open tickets" :value="$assignedOpen" icon="wrench" :href="route('technician.tickets.index')" />
        <x-stat-card label="Resolved today" :value="$resolvedToday" icon="check-circle" tone="sky" />
        <x-stat-card label="Unassigned in queue" :value="$unassigned" icon="lifebuoy" tone="amber" :href="route('technician.tickets.index')" />
    </div>

    <div class="surface-dark mt-5 p-7">
        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-xl font-semibold !text-white">Ready to pick up the next ticket?</h3>
                <p class="mt-1 text-sm text-slate-400">Open the queue to claim unassigned tickets and update ticket progress.</p>
            </div>
            <a href="{{ route('technician.tickets.index') }}" class="btn-primary shrink-0">Open ticket queue <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
    </div>
</x-app-layout>
