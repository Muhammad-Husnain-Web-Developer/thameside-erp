<?php

namespace App\Modules\Core\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Single source of truth for permissions and the role matrix. Each module
 * registers its own permission group here as it is built, and the seeder
 * creates whatever is declared.
 */
final class PermissionRegistry
{
    /** Roles available in the ERP. */
    public const ROLES = ['admin', 'accountant', 'sales', 'warehouse'];

    /**
     * Permission groups: group label => [permission => description].
     *
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'Overview' => [
                'dashboard.view' => 'View the operations dashboard',
                'activity.view' => 'View the audit trail',
            ],
            'Users & Access' => [
                'users.view' => 'View staff accounts',
                'users.create' => 'Invite staff accounts',
                'users.update' => 'Edit staff accounts and roles',
                'users.delete' => 'Remove staff accounts',
            ],
            'Inventory' => [
                'products.view' => 'View the product catalogue',
                'products.create' => 'Add products',
                'products.update' => 'Edit products and pricing',
                'products.delete' => 'Remove products',
                'stock.view' => 'View stock levels and the movement ledger',
                'stock.adjust' => 'Record stock movements',
            ],
            'Purchasing' => [
                'suppliers.view' => 'View suppliers',
                'suppliers.create' => 'Add suppliers',
                'suppliers.update' => 'Edit suppliers',
                'purchase_orders.view' => 'View purchase orders',
                'purchase_orders.create' => 'Raise purchase orders',
                'purchase_orders.receive' => 'Receive goods against an order',
                'bills.view' => 'View supplier bills',
                'bills.pay' => 'Record supplier payments',
            ],
            'Sales' => [
                'customers.view' => 'View customers',
                'customers.create' => 'Add customers',
                'customers.update' => 'Edit customers',
                'sales_orders.view' => 'View sales orders',
                'sales_orders.create' => 'Raise sales orders',
                'invoices.view' => 'View invoices',
                'invoices.create' => 'Invoice a sales order',
                'invoices.pay' => 'Record customer receipts',
            ],
            'People' => [
                'hr.view' => 'View employees and departments',
                'hr.manage' => 'Add and edit employees',
                'payroll.view' => 'View payroll runs and payslips',
                'payroll.run' => 'Run monthly payroll',
            ],
            'Insights' => [
                'ai.view' => 'View demand forecasts and anomaly detection',
            ],
            'Reporting' => [
                'reports.view' => 'View sales and stock reports',
                'reports.export' => 'Export reports to Excel',
            ],
            'Accounting' => [
                'journals.view' => 'View the general ledger and reports',
                'journals.create' => 'Post manual journals',
                'accounts.view' => 'View the chart of accounts',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(array_merge(...array_values(self::groups())));
    }

    /**
     * Role => permission list. `*` grants every permission in the registry.
     *
     * @return array<string, array<int, string>>
     */
    public static function roleMatrix(): array
    {
        return [
            'admin' => ['*'],
            'accountant' => [
                'dashboard.view',
                'activity.view',
                'products.view',
                'stock.view',
                'journals.view',
                'journals.create',
                'accounts.view',
                'suppliers.view',
                'suppliers.create',
                'suppliers.update',
                'purchase_orders.view',
                'bills.view',
                'bills.pay',
                'customers.view',
                'sales_orders.view',
                'invoices.view',
                'invoices.pay',
                'reports.view',
                'reports.export',
                'hr.view',
                'payroll.view',
                'payroll.run',
                'ai.view',
            ],
            'sales' => [
                'dashboard.view',
                'products.view',
                'stock.view',
                'customers.view',
                'customers.create',
                'customers.update',
                'sales_orders.view',
                'sales_orders.create',
                'invoices.view',
                'invoices.create',
                'reports.view',
                'ai.view',
            ],
            'warehouse' => [
                'dashboard.view',
                'products.view',
                'products.create',
                'products.update',
                'stock.view',
                'stock.adjust',
                'suppliers.view',
                'purchase_orders.view',
                'purchase_orders.create',
                'purchase_orders.receive',
                'reports.view',
                'ai.view',
            ],
        ];
    }

    /**
     * Roles that a user can be assigned, used by the user management forms.
     *
     * @return array<int, string>
     */
    public static function assignableRoles(): array
    {
        return Role::query()->orderBy('name')->pluck('name')->all();
    }

    /**
     * Create any declared permission that does not exist yet.
     */
    public static function syncPermissions(): void
    {
        foreach (self::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
