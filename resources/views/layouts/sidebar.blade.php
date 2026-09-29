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

    if ($role !== 'super_admin') {
        $groups['My Workspace'] = [
            ['Attendance', 'employee.attendance.index', ['employee.attendance.*'], 'fingerprint'],
            ['Overtime', 'employee.overtime.index', ['employee.overtime.*'], 'clock'],
            ['Leave', 'employee.leave.index', ['employee.leave.*'], 'calendar'],
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
            ['Overtime', 'hr.overtime.index', ['hr.overtime.*'], 'clock'],
            ['Leave', 'hr.leave.index', ['hr.leave.*'], 'calendar'],
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
    class="fixed inset-y-0 left-0 z-40 flex w-[17rem] flex-col bg-ink-900 text-slate-300 transition-transform duration-300 ease-out lg:inset-y-3 lg:left-3 lg:translate-x-0 lg:rounded-3xl lg:shadow-2xl lg:shadow-ink-950/20"
>
    {{-- Decorative glow --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden lg:rounded-3xl">
        <div class="absolute -top-20 -left-16 h-56 w-56 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="absolute bottom-10 -right-24 h-48 w-48 rounded-full bg-teal-400/10 blur-3xl"></div>
    </div>

    <div class="relative flex h-20 shrink-0 items-center gap-3 px-6">
        <x-application-logo />
        <div class="leading-tight">
            <div class="text-[15px] font-bold tracking-tight text-white">SEMS</div>
            <div class="text-[11px] text-slate-500">Smart Employee Management</div>
        </div>
        <button @click="sidebarOpen = false" class="ms-auto rounded-lg p-1.5 text-slate-500 hover:bg-white/5 hover:text-white lg:hidden" aria-label="Close menu">
            <x-icon name="x" />
        </button>
    </div>

    <nav class="relative flex-1 space-y-0.5 overflow-y-auto px-3 pb-4 [scrollbar-width:thin] [scrollbar-color:rgba(255,255,255,0.08)_transparent]">
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

    <div class="relative m-3 rounded-2xl border border-white/5 bg-white/[0.03] p-3">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 text-sm font-bold text-white">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-[13px] font-semibold text-white">{{ $user->name }}</div>
                <div class="truncate text-[11px] capitalize text-emerald-400/90">{{ str_replace('_', ' ', $role) }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg p-2 text-slate-500 transition hover:bg-white/5 hover:text-rose-400" title="Log out">
                    <x-icon name="logout" class="h-[18px] w-[18px]" />
                </button>
            </form>
        </div>
    </div>
</aside>
