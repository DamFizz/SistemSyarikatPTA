@php
    $role = auth()->user()->role;
    $primaryOffice = \App\Models\Office::first();
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-300 flex flex-col transition-transform duration-200 lg:translate-x-0"
>
    <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-800 shrink-0">
        <div class="h-8 w-8 rounded-lg bg-emerald-500 flex items-center justify-center text-white font-bold text-sm">S</div>
        <span class="text-white font-semibold text-lg tracking-tight">SEMS</span>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <x-nav-section-link :href="route('dashboard')" :active="request()->routeIs('dashboard') || request()->routeIs('*.dashboard')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10m-9 3h0" /></svg>
            </x-slot>
            Dashboard
        </x-nav-section-link>

        <x-nav-section-link :href="route('announcements.index')" :active="request()->routeIs('announcements.*')">
            <x-slot name="icon">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
            </x-slot>
            Announcements
        </x-nav-section-link>

        @if ($role !== 'super_admin')
            <x-nav-section-link :href="route('employee.attendance.index')" :active="request()->routeIs('employee.attendance.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot>
                My Attendance
            </x-nav-section-link>

            <x-nav-section-link :href="route('employee.overtime.index')" :active="request()->routeIs('employee.overtime.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M12 22a10 10 0 100-20 10 10 0 000 20z" /></svg>
                </x-slot>
                My Overtime
            </x-nav-section-link>

            <x-nav-section-link :href="route('employee.leave.index')" :active="request()->routeIs('employee.leave.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </x-slot>
                My Leave
            </x-nav-section-link>

            <x-nav-section-link :href="route('employee.payslips.index')" :active="request()->routeIs('employee.payslips.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m-6 4h6m-6 4h4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                </x-slot>
                My Payslips
            </x-nav-section-link>

            <x-nav-section-link :href="route('employee.tickets.index')" :active="request()->routeIs('employee.tickets.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M5.636 5.636l3.536 3.536m0 5.656l-3.536 3.536M12 12m-3 0a3 3 0 106 0 3 3 0 00-6 0z" /></svg>
                </x-slot>
                Helpdesk
            </x-nav-section-link>
        @endif

        @if ($role === 'super_admin')
            <x-nav-section-link :href="route('super-admin.offices.index')" :active="request()->routeIs('super-admin.offices.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </x-slot>
                Office Locations
            </x-nav-section-link>

            <x-nav-section-link :href="route('super-admin.audit-logs.index')" :active="request()->routeIs('super-admin.audit-logs.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </x-slot>
                Audit Log
            </x-nav-section-link>
        @endif

        @if ($role === 'technician')
            <x-nav-section-link :href="route('technician.tickets.index')" :active="request()->routeIs('technician.tickets.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M5.636 5.636l3.536 3.536m0 5.656l-3.536 3.536M12 12m-3 0a3 3 0 106 0 3 3 0 00-6 0z" /></svg>
                </x-slot>
                Ticket Queue
            </x-nav-section-link>
        @endif

        @if ($role === 'manager')
            <x-nav-section-link :href="route('manager.employees.index')" :active="request()->routeIs('manager.employees.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </x-slot>
                My Team
            </x-nav-section-link>

            <x-nav-section-link :href="route('manager.overtime.index')" :active="request()->routeIs('manager.overtime.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot>
                Overtime Approvals
            </x-nav-section-link>

            <x-nav-section-link :href="route('manager.leave.index')" :active="request()->routeIs('manager.leave.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </x-slot>
                Leave Approvals
            </x-nav-section-link>
        @endif

        @if (in_array($role, ['hr_admin', 'super_admin']))
            <div class="pt-4 mt-4 border-t border-slate-800 text-xs uppercase tracking-wider text-slate-500 px-3">Employee Management</div>

            <x-nav-section-link :href="route('hr.employees.index')" :active="request()->routeIs('hr.employees.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </x-slot>
                Employees
            </x-nav-section-link>

            <x-nav-section-link :href="route('hr.departments.index')" :active="request()->routeIs('hr.departments.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M19 21H5m0 0H3m8-14h.01M11 11h.01M11 15h.01M7 7h.01M7 11h.01M7 15h.01" /></svg>
                </x-slot>
                Departments
            </x-nav-section-link>

            <div class="pt-4 mt-4 border-t border-slate-800 text-xs uppercase tracking-wider text-slate-500 px-3">Attendance</div>

            <x-nav-section-link :href="route('hr.attendance.index')" :active="request()->routeIs('hr.attendance.index')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot>
                Attendance Records
            </x-nav-section-link>

            @if ($primaryOffice)
                <x-nav-section-link :href="route('hr.attendance.qr-display', $primaryOffice)" :active="request()->routeIs('hr.attendance.qr-display')">
                    <x-slot name="icon">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11a3 3 0 100-6 3 3 0 000 6zm12 0a3 3 0 100-6 3 3 0 000 6zM3 20a3 3 0 106 0 3 3 0 00-6 0z" /></svg>
                    </x-slot>
                    QR Checkpoint Display
                </x-nav-section-link>
            @endif

            <x-nav-section-link :href="route('hr.overtime.index')" :active="request()->routeIs('hr.overtime.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </x-slot>
                Overtime
            </x-nav-section-link>

            <x-nav-section-link :href="route('hr.leave.index')" :active="request()->routeIs('hr.leave.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </x-slot>
                Leave
            </x-nav-section-link>

            <x-nav-section-link :href="route('hr.payroll.index')" :active="request()->routeIs('hr.payroll.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m-6 4h6m-6 4h4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                </x-slot>
                Payroll
            </x-nav-section-link>

            <x-nav-section-link :href="route('hr.tickets.index')" :active="request()->routeIs('hr.tickets.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M5.636 5.636l3.536 3.536m0 5.656l-3.536 3.536M12 12m-3 0a3 3 0 106 0 3 3 0 00-6 0z" /></svg>
                </x-slot>
                Helpdesk Tickets
            </x-nav-section-link>

            <x-nav-section-link :href="route('hr.reports.index')" :active="request()->routeIs('hr.reports.*')">
                <x-slot name="icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                </x-slot>
                Reports
            </x-nav-section-link>
        @endif

        {{-- More module links are added per role as each phase is built. --}}
    </nav>

    <div class="border-t border-slate-800 p-3">
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg text-slate-400 text-xs uppercase tracking-wider">
            {{ str_replace('_', ' ', $role) }}
        </div>
    </div>
</aside>
