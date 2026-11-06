<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Models\User;

/**
 * Builds the sidebar navigation, filtered by the signed-in user's permissions
 * so role-aware menus live in one place instead of in every Vue page.
 */
final class Navigation
{
    /**
     * @return array<int, array{label: string, items: array<int, array{label: string, href: string, match: string, icon: string}>}>
     */
    public static function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $groups = [
            [
                'label' => 'Overview',
                'items' => [
                    self::item($user, 'Dashboard', 'dashboard', '/', 'dashboard.view', 'grid'),
                ],
            ],
            [
                'label' => 'Inventory',
                'items' => [
                    self::item($user, 'Products', 'inventory.products.index', '/inventory/products', 'products.view', 'box'),
                    self::item($user, 'Stock ledger', 'inventory.stock.index', '/inventory/stock', 'stock.view', 'truck'),
                ],
            ],
            [
                'label' => 'Purchasing',
                'items' => [
                    self::item($user, 'Suppliers', 'purchasing.suppliers.index', '/purchasing/suppliers', 'suppliers.view', 'building'),
                    self::item($user, 'Purchase orders', 'purchasing.purchase-orders.index', '/purchasing/purchase-orders', 'purchase_orders.view', 'truck'),
                    self::item($user, 'Supplier bills', 'purchasing.bills.index', '/purchasing/bills', 'bills.view', 'receipt'),
                ],
            ],
            [
                'label' => 'Sales',
                'items' => [
                    self::item($user, 'Customers', 'sales.customers.index', '/sales/customers', 'customers.view', 'users'),
                    self::item($user, 'Sales orders', 'sales.orders.index', '/sales/orders', 'sales_orders.view', 'truck'),
                    self::item($user, 'Invoices', 'sales.invoices.index', '/sales/invoices', 'invoices.view', 'receipt'),
                ],
            ],
            [
                'label' => 'Insights',
                'items' => [
                    self::item($user, 'AI insights', 'ai.index', '/ai', 'ai.view', 'sparkles'),
                ],
            ],
            [
                'label' => 'Reports',
                'items' => [
                    self::item($user, 'Sales summary', 'reports.sales', '/reports/sales', 'reports.view', 'chart'),
                    self::item($user, 'Stock valuation', 'reports.stock', '/reports/stock', 'reports.view', 'box'),
                ],
            ],
            [
                'label' => 'Accounting',
                'items' => [
                    self::item($user, 'Journals', 'accounting.journals.index', '/accounting/journals', 'journals.view', 'book'),
                    self::item($user, 'Trial balance', 'accounting.reports.trial-balance', '/accounting/reports/trial-balance', 'journals.view', 'chart'),
                    self::item($user, 'VAT return', 'accounting.reports.vat-return', '/accounting/reports/vat-return', 'journals.view', 'receipt'),
                    self::item($user, 'Chart of accounts', 'accounting.accounts.index', '/accounting/accounts', 'accounts.view', 'building'),
                ],
            ],
            [
                'label' => 'People',
                'items' => [
                    self::item($user, 'Employees', 'people.employees.index', '/people/employees', 'hr.view', 'users'),
                    self::item($user, 'Payroll', 'people.payroll.index', '/people/payroll', 'payroll.view', 'banknotes'),
                ],
            ],
            [
                'label' => 'Administration',
                'items' => [
                    self::item($user, 'Staff accounts', 'settings.users.index', '/settings/users', 'users.view', 'users'),
                    self::item($user, 'Activity log', 'settings.activity.index', '/settings/activity', 'activity.view', 'history'),
                ],
            ],
        ];

        return array_values(array_filter(array_map(
            fn (array $group) => [
                'label' => $group['label'],
                'items' => array_values(array_filter(
                    $group['items'],
                    fn (?array $item) => $item !== null,
                )),
            ],
            $groups,
        ), fn (array $group) => $group['items'] !== []));
    }

    /**
     * @return array{label: string, href: string, match: string, icon: string}|null
     */
    private static function item(User $user, string $label, string $routeName, string $match, string $permission, string $icon): ?array
    {
        if (! $user->can($permission)) {
            return null;
        }

        return [
            'label' => $label,
            'href' => route($routeName),
            'match' => $match,
            'icon' => $icon,
        ];
    }
}
