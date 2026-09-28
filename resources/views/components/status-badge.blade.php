@props(['status'])

@php
$classes = match ($status) {
    'active', 'approved', 'resolved', 'closed', 'present', 'paid' => 'bg-emerald-100 text-emerald-700',
    'probation', 'pending', 'processing', 'assigned', 'draft', 'open' => 'bg-amber-100 text-amber-700',
    'resigned', 'terminated', 'rejected', 'absent', 'critical' => 'bg-red-100 text-red-700',
    'suspended', 'late', 'half_day', 'in_progress', 'waiting_user' => 'bg-orange-100 text-orange-700',
    default => 'bg-slate-100 text-slate-600',
};
@endphp

<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $classes }}">
    {{ str($status)->replace('_', ' ')->title() }}
</span>
