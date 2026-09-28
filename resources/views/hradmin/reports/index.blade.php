<x-app-layout title="Reports">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Reports</h2>
    </x-slot>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ([
            ['route' => 'hr.reports.attendance', 'title' => 'Attendance Report', 'desc' => 'Clock in/out records by date, department and status.'],
            ['route' => 'hr.reports.leave', 'title' => 'Leave Report', 'desc' => 'Leave applications by date range, department and status.'],
            ['route' => 'hr.reports.overtime', 'title' => 'Overtime Report', 'desc' => 'Overtime requests and payout amounts.'],
            ['route' => 'hr.reports.payroll', 'title' => 'Payroll Report', 'desc' => 'Payroll runs by period and department.'],
            ['route' => 'hr.reports.helpdesk', 'title' => 'Helpdesk Report', 'desc' => 'Ticket volume, priority and resolution status.'],
        ] as $report)
            <a href="{{ route($report['route']) }}" class="bg-white rounded-xl border border-slate-200 p-5 hover:border-emerald-400 transition">
                <h3 class="font-semibold text-slate-800">{{ $report['title'] }}</h3>
                <p class="text-sm text-slate-500 mt-1">{{ $report['desc'] }}</p>
            </a>
        @endforeach
    </div>
</x-app-layout>
