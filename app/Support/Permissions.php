<?php

namespace App\Support;

/**
 * Administrator permission catalogue. Each module has two levels:
 * "view" opens its pages, "manage" also allows changes. Administrators
 * without a role are full (super) administrators.
 */
class Permissions
{
    public const MODULES = [
        'students' => 'Students & Trainees',
        'teachers' => 'Teachers & Trainers',
        'parents' => 'Parents',
        'admissions' => 'Admissions',
        'academics' => 'Programs, classes & timetable',
        'exams' => 'Exams, question bank & lectures',
        'certificates' => 'Certificates',
        'library' => 'Library & downloads',
        'finance' => 'Invoices, payments & expenses',
        'payroll' => 'Payroll',
        'ledger' => 'Ledger & accounts',
        'communication' => 'Announcements, messages & contact',
        'content' => 'News, site content & media',
        'support' => 'Support tickets',
        'reports' => 'Reports & analytics',
        'branches' => 'Branches',
        'users' => 'Accounts, roles & delegations',
        'settings' => 'Settings & integrations',
        'backups' => 'Backup & recovery',
        'security' => 'Activity & security log',
        'assistant' => 'AI assistant',
    ];

    public const ACTIONS = ['view', 'manage'];

    /** @return list<string> every "module.action" pair */
    public static function all(): array
    {
        $all = [];

        foreach (array_keys(self::MODULES) as $module) {
            foreach (self::ACTIONS as $action) {
                $all[] = "{$module}.{$action}";
            }
        }

        return $all;
    }

    /** Keep only known permission strings. */
    public static function clean(array $permissions): array
    {
        return array_values(array_intersect(self::all(), $permissions));
    }

    /** Does a permission set allow this action? "manage" implies "view". */
    public static function allows(array $permissions, string $module, string $action = 'view'): bool
    {
        return in_array("{$module}.manage", $permissions, true)
            || ($action === 'view' && in_array("{$module}.view", $permissions, true));
    }
}
