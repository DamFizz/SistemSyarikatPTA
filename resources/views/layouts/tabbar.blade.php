@php
    $user = auth()->user();
    $role = $user->role;
    $hasProfile = (bool) $user->employee;

    // [label, route, active patterns, icon] — the phone's main destinations per role.
    $tabs = match (true) {
        $role === 'super_admin' => [
            ['Home', 'dashboard', ['dashboard', '*.dashboard'], 'home'],
            ['People', 'hr.employees.index', ['hr.employees.*'], 'users'],
            ['Offices', 'super-admin.offices.index', ['super-admin.offices.*'], 'wifi'],
            ['Reports', 'hr.reports.index', ['hr.reports.*'], 'chart'],
        ],
        $role === 'hr_admin' => [
            ['Home', 'dashboard', ['dashboard', '*.dashboard'], 'home'],
            ['People', 'hr.employees.index', ['hr.employees.*'], 'users'],
            ['Records', 'hr.attendance.index', ['hr.attendance.*'], 'calendar'],
            ['Payroll', 'hr.payroll.index', ['hr.payroll.*'], 'banknotes'],
        ],
        $role === 'manager' => [
            ['Home', 'dashboard', ['dashboard', '*.dashboard'], 'home'],
            ['Leave', 'manager.leave.index', ['manager.leave.*'], 'calendar'],
            ['Overtime', 'manager.overtime.index', ['manager.overtime.*'], 'check-badge'],
            ['Team', 'manager.employees.index', ['manager.employees.*'], 'users'],
        ],
        $role === 'technician' => [
            ['Home', 'dashboard', ['dashboard', '*.dashboard'], 'home'],
            ['Queue', 'technician.tickets.index', ['technician.tickets.*'], 'wrench'],
            ['News', 'announcements.index', ['announcements.*'], 'megaphone'],
            ['Helpdesk', 'employee.tickets.index', ['employee.tickets.*'], 'lifebuoy'],
        ],
        default => [
            ['Home', 'dashboard', ['dashboard', '*.dashboard'], 'home'],
            ['Leave', 'employee.leave.index', ['employee.leave.*'], 'calendar'],
            ['Overtime', 'employee.overtime.index', ['employee.overtime.*'], 'clock'],
            ['Payslips', 'employee.payslips.index', ['employee.payslips.*'], 'banknotes'],
        ],
    };

    // Self-service tabs need an employee profile.
    $tabs = array_values(array_filter($tabs, fn ($tab) => $hasProfile || ! str_starts_with($tab[1], 'employee.')));

    // Staff clock in from the raised centre button.
    $centre = $hasProfile ? ['Clock', 'employee.attendance.index', ['employee.attendance.*'], 'fingerprint'] : null;

    $items = [...$tabs, ['More', null, [], 'menu']];
    $activeIndex = collect($items)->search(fn ($item) => $item[1] && request()->routeIs(...$item[2]));
    $centreActive = $centre && request()->routeIs(...$centre[2]);
    $groups = \App\Support\Navigation::groupsFor($user);
@endphp

@include('layouts.partials.tabdock')
