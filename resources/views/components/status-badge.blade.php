@props(['status'])

@php
$tone = match ($status) {
    'active', 'approved', 'resolved', 'closed', 'present', 'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15 [--dot:theme(colors.emerald.500)]',
    'probation', 'pending', 'processing', 'assigned', 'draft', 'open' => 'bg-amber-50 text-amber-700 ring-amber-600/15 [--dot:theme(colors.amber.500)]',
    'resigned', 'terminated', 'rejected', 'absent', 'critical', 'urgent' => 'bg-rose-50 text-rose-700 ring-rose-600/15 [--dot:theme(colors.rose.500)]',
    'suspended', 'late', 'half_day', 'in_progress', 'waiting_user', 'high' => 'bg-orange-50 text-orange-700 ring-orange-600/15 [--dot:theme(colors.orange.500)]',
    'on_leave', 'public_holiday', 'work_from_home', 'generated' => 'bg-sky-50 text-sky-700 ring-sky-600/15 [--dot:theme(colors.sky.500)]',
    default => 'bg-slate-100 text-slate-600 ring-slate-500/15 [--dot:theme(colors.slate.400)]',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {$tone}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-[var(--dot)]"></span>
    {{ str($status)->replace('_', ' ')->title() }}
</span>
