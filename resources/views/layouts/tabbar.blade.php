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

    $items = $tabs;
    if ($centre) {
        array_splice($items, 2, 0, [$centre]);
    }
    $items[] = ['More', null, [], 'menu'];

    $activeIndex = collect($items)->search(fn ($item) => $item[1] && request()->routeIs(...$item[2]));
@endphp

<nav class="tabbar" aria-label="Main" style="--tabs: {{ count($items) }}; --tab-index: {{ $activeIndex === false ? 0 : $activeIndex }}">
    @if ($activeIndex !== false && $items[$activeIndex] !== $centre)
        <span class="tab-indicator"></span>
    @endif

    @foreach ($items as $i => [$label, $routeName, $patterns, $icon])
        @if ($routeName === null)
            <button type="button" @click="sidebarOpen = true; navigator.vibrate?.(8)" class="tab" :class="sidebarOpen && 'tab-active'">
                <x-icon :name="$icon" class="h-[22px] w-[22px]" />
                <span>{{ $label }}</span>
            </button>
        @elseif ($centre && $label === $centre[0])
            <a href="{{ route($routeName) }}" class="tab !justify-start" onclick="navigator.vibrate?.(10)" aria-label="Attendance">
                <span class="tab-fab {{ $activeIndex === $i ? 'ring-emerald-200' : '' }}"><x-icon :name="$icon" class="h-7 w-7" /></span>
                <span class="{{ $activeIndex === $i ? 'text-emerald-700' : '' }}">{{ $label }}</span>
            </a>
        @else
            <a href="{{ route($routeName) }}" class="tab {{ $activeIndex === $i ? 'tab-active' : '' }}" onclick="navigator.vibrate?.(8)"
               @if ($activeIndex === $i) aria-current="page" @endif>
                <x-icon :name="$icon" class="h-[22px] w-[22px] {{ $activeIndex === $i ? 'animate-pop-in' : '' }}" />
                <span>{{ $label }}</span>
            </a>
        @endif
    @endforeach
</nav>
