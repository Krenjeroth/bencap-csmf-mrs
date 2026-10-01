<?php

namespace App\Support;

/**
 * The permissions checked in code (playbook 2.6, ADR 0004).
 *
 * Seeded as protected rows: the role editor can grant or revoke them, but
 * they cannot be renamed or deleted, because code refers to them by title.
 */
final class PermissionCatalog
{
    /** @var array<string, string> title => description */
    public const PERMISSIONS = [
        'dashboard.view' => 'See the dashboard and analytics',

        'feedback.view' => 'List and open feedback',
        'feedback.encode' => 'Encode paper forms and record forms issued',
        'feedback.void' => 'Void a feedback record with a reason',

        'reports.view' => 'View summary reports',
        'reports.export' => 'Export summary reports to PDF, Excel or Word',

        'offices.view' => 'View offices',
        'offices.create' => 'Add offices',
        'offices.update' => 'Edit offices',
        'offices.delete' => 'Delete offices',

        'service-types.view' => 'View service types',
        'service-types.create' => 'Add service types',
        'service-types.update' => 'Edit service types',
        'service-types.delete' => 'Delete service types',

        'services.view' => 'View services',
        'services.create' => 'Add services',
        'services.update' => 'Edit services',
        'services.delete' => 'Delete services',

        'users.view' => 'View user accounts',
        'users.create' => 'Add user accounts',
        'users.update' => 'Edit user accounts, assign roles, reset passwords',
        'users.delete' => 'Delete user accounts',

        'roles.view' => 'View roles',
        'roles.create' => 'Add roles',
        'roles.update' => 'Edit roles and their permissions',
        'roles.delete' => 'Delete roles',

        'permissions.view' => 'View permissions',
        'permissions.create' => 'Add permissions',
        'permissions.update' => 'Edit permission descriptions',
        'permissions.delete' => 'Delete permissions',

        'kiosks.manage' => 'Register kiosk tablets and issue activation codes',
        'settings.manage' => 'Edit the form introduction and report signatories',
        'audit-logs.view' => 'Read the audit log',
    ];

    /**
     * Admin is office-scoped (ADR 0004): it works with its own office's
     * feedback and reports and can look up master data, nothing more.
     *
     * @var list<string>
     */
    public const ADMIN_DEFAULTS = [
        'dashboard.view',
        'feedback.view',
        'feedback.encode',
        'reports.view',
        'reports.export',
        'offices.view',
        'service-types.view',
        'services.view',
    ];

    /** @return list<string> */
    public static function titles(): array
    {
        return array_keys(self::PERMISSIONS);
    }
}
