<?php

namespace App\Support;

use App\Models\User;

/**
 * The app's menu for a user, shared by the desktop sidebar and the phone's morphing menu.
 */
final class Navigation
{
    /**
     * @return array<string, list<array{0: string, 1: string, 2: list<string>, 3: string}>> section => [label, route, active patterns, icon]
     */
    public static function groupsFor(User $user): array
    {
        $role = $user->role;

        $groups = [
            '' => [
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

        if (in_array($role, ['hr_admin', 'super_admin'], true)) {
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

        return $groups;
    }
}
