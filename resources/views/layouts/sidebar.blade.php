@php
    $user = auth()->user();
    $role = $user->role;

    // [label, route, active pattern, icon]
    $groups = [
        null => [
            ['Dashboard', 'dashboard', ['dashboard', '*.dashboard'], 'home'],
            ['Announcements', 'announcements.index', ['announcements.*'], 'megaphone'],
        ],
    ];

    // Self-service pages only make sense for accounts linked to an employee profile.
    if ($user->employee) {
        $groups['My Workspace'] = [
            ['Attendance', 'employee.attendance.index', ['employee.attendance.*'], 'fingerprint'],
            [$role === 'hr_admin' ? 'Request Overtime' : 'Overtime', 'employee.overtime.index', ['employee.overtime.*'], 'clock'],
            [$role === 'hr_admin' ? 'Request Leave' : 'Leave', 'employee.leave.index', ['employee.leave.*'], 'calendar'],
            ['Payslips', 'employee.payslips.index', ['employee.payslips.*'], 'banknotes'],
            ['Helpdesk', 'employee.tickets.index', ['employee.tickets.*'], 'lifebuoy'],
        ];
    }

    if ($role === 'technician') {
        $groups['Support'] = [
            ['Ticket Queue', 'technician.tickets.index', ['technician.tickets.*'], 'wrench'],
        ];
    }

    if ($role === 'manager') {
        $groups['My Team'] = [
            ['Team Members', 'manager.employees.index', ['manager.employees.*'], 'users'],
            ['Overtime Approvals', 'manager.overtime.index', ['manager.overtime.*'], 'check-badge'],
            ['Leave Approvals', 'manager.leave.index', ['manager.leave.*'], 'calendar'],
        ];
    }

    if (in_array($role, ['hr_admin', 'super_admin'])) {
        $groups['People'] = [
            ['Employees', 'hr.employees.index', ['hr.employees.*'], 'users'],
            ['Departments', 'hr.departments.index', ['hr.departments.*'], 'building'],
        ];
        $groups['Operations'] = [
            ['Attendance Records', 'hr.attendance.index', ['hr.attendance.*'], 'fingerprint'],
            ['Overtime Records', 'hr.overtime.index', ['hr.overtime.*'], 'clock'],
            ['Leave Records', 'hr.leave.index', ['hr.leave.*'], 'calendar'],
            ['Working Hours', 'hr.work-hours.index', ['hr.work-hours.*'], 'shield'],
            ['Public Holidays', 'hr.public-holidays.index', ['hr.public-holidays.*'], 'sparkles'],
            ['Payroll', 'hr.payroll.index', ['hr.payroll.*'], 'banknotes'],
            ['Helpdesk Tickets', 'hr.tickets.index', ['hr.tickets.*'], 'lifebuoy'],
            ['Reports', 'hr.reports.index', ['hr.reports.*'], 'chart'],
        ];
    }

    if ($role === 'super_admin') {
        $groups['System'] = [
            ['Offices & WiFi', 'super-admin.offices.index', ['super-admin.offices.*'], 'wifi'],
            ['Audit Log', 'super-admin.audit-logs.index', ['super-admin.audit-logs.*'], 'shield'],
        ];
    }
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed left-0 top-0 z-40 flex h-viewport w-[17rem] max-w-[85vw] flex-col overscroll-contain border-r border-white/60 bg-white/80 text-slate-600 shadow-[var(--glass-rim),var(--glass-shadow-lift)] backdrop-blur-2xl backdrop-saturate-[1.8] transition-transform duration-300 ease-out lg:inset-y-3 lg:left-3 lg:h-auto lg:max-w-none lg:translate-x-0 lg:rounded-[1.75rem] lg:border lg:bg-white/50"
>

    <div class="relative flex h-20 shrink-0 items-center gap-3 px-6">
        <x-application-logo />
        <div class="leading-tight">
            <div class="text-[15px] font-bold tracking-tight text-slate-900">SEMS</div>
            <div class="text-[11px] text-slate-400">Smart Employee Management</div>
        </div>
        <button @click="sidebarOpen = false" class="ms-auto rounded-full p-1.5 text-slate-400 hover:bg-white/70 hover:text-slate-900 lg:hidden" aria-label="Close menu">
            <x-icon name="x" />
        </button>
    </div>

    <nav class="relative flex-1 space-y-0.5 overflow-y-auto overscroll-contain px-3 pb-4 [scrollbar-width:thin] [scrollbar-color:rgba(15,23,42,0.12)_transparent]">
        @foreach ($groups as $label => $items)
            @if ($label)
                <div class="nav-section">{{ $label }}</div>
            @endif
            @foreach ($items as [$text, $routeName, $patterns, $icon])
                <x-nav-section-link :href="route($routeName)" :active="request()->routeIs(...$patterns)" :icon="$icon">
                    {{ $text }}
                </x-nav-section-link>
            @endforeach
        @endforeach
    </nav>

    <div class="glass-thin relative m-3 rounded-2xl p-3">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 text-sm font-bold text-white">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-[13px] font-semibold text-slate-900">{{ $user->name }}</div>
                <div class="truncate text-[11px] capitalize text-emerald-600">{{ str_replace('_', ' ', $role) }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-full p-2 text-slate-400 transition hover:bg-rose-500/10 hover:text-rose-600" title="Log out">
                    <x-icon name="logout" class="h-[18px] w-[18px]" />
                </button>
            </form>
        </div>
    </div>
</aside>
