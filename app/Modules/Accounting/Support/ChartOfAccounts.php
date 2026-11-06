<?php

namespace App\Modules\Accounting\Support;

use App\Modules\Accounting\Models\NominalAccount;

/**
 * Thameside's nominal ledger. Codes are stable: purchase and sales posting
 * reference these constants rather than looking accounts up by name.
 */
final class ChartOfAccounts
{
    public const BANK = '1000';
    public const PETTY_CASH = '1010';
    public const TRADE_DEBTORS = '1100';
    public const INVENTORY = '1200';
    public const VAT_RECEIVABLE = '1300';
    public const EQUIPMENT = '1500';

    public const TRADE_CREDITORS = '2100';
    public const VAT_PAYABLE = '2200';
    public const PAYE_PAYABLE = '2300';
    public const PENSION_PAYABLE = '2400';

    public const SHARE_CAPITAL = '3000';
    public const RETAINED_EARNINGS = '3100';

    public const SALES = '4000';
    public const DELIVERY_INCOME = '4100';

    public const COST_OF_SALES = '5000';
    public const RENT = '6000';
    public const WAGES = '6100';
    public const UTILITIES = '6200';
    public const OFFICE = '6300';
    public const TRANSPORT = '6400';
    public const BANK_CHARGES = '6500';

    /**
     * @return array<string, array{name: string, type: string, description: string, system: bool}>
     */
    public static function accounts(): array
    {
        return [
            self::BANK => ['name' => 'Bank current account', 'type' => NominalAccount::TYPE_ASSET, 'description' => 'Lloyds Bank current account.', 'system' => true],
            self::PETTY_CASH => ['name' => 'Petty cash', 'type' => NominalAccount::TYPE_ASSET, 'description' => 'Depot cash float.', 'system' => false],
            self::TRADE_DEBTORS => ['name' => 'Trade debtors', 'type' => NominalAccount::TYPE_ASSET, 'description' => 'Amounts owed by customers.', 'system' => true],
            self::INVENTORY => ['name' => 'Inventory', 'type' => NominalAccount::TYPE_ASSET, 'description' => 'Stock at cost held in the depot.', 'system' => true],
            self::VAT_RECEIVABLE => ['name' => 'VAT receivable', 'type' => NominalAccount::TYPE_ASSET, 'description' => 'Input VAT reclaimable on purchases.', 'system' => true],
            self::EQUIPMENT => ['name' => 'Plant & equipment', 'type' => NominalAccount::TYPE_ASSET, 'description' => 'Warehouse equipment and vehicles.', 'system' => false],
            self::TRADE_CREDITORS => ['name' => 'Trade creditors', 'type' => NominalAccount::TYPE_LIABILITY, 'description' => 'Amounts owed to suppliers.', 'system' => true],
            self::VAT_PAYABLE => ['name' => 'VAT payable', 'type' => NominalAccount::TYPE_LIABILITY, 'description' => 'Output VAT collected on sales.', 'system' => true],
            self::PAYE_PAYABLE => ['name' => 'PAYE & NI payable', 'type' => NominalAccount::TYPE_LIABILITY, 'description' => 'Payroll deductions due to HMRC.', 'system' => true],
            self::PENSION_PAYABLE => ['name' => 'Pension payable', 'type' => NominalAccount::TYPE_LIABILITY, 'description' => 'Employee pension contributions due to the scheme.', 'system' => true],
            self::SHARE_CAPITAL => ['name' => 'Share capital', 'type' => NominalAccount::TYPE_EQUITY, 'description' => 'Issued share capital.', 'system' => false],
            self::RETAINED_EARNINGS => ['name' => 'Retained earnings', 'type' => NominalAccount::TYPE_EQUITY, 'description' => 'Accumulated profits.', 'system' => true],
            self::SALES => ['name' => 'Sales', 'type' => NominalAccount::TYPE_INCOME, 'description' => 'Goods sold, net of VAT.', 'system' => true],
            self::DELIVERY_INCOME => ['name' => 'Delivery income', 'type' => NominalAccount::TYPE_INCOME, 'description' => 'Delivery charges recharged to customers.', 'system' => false],
            self::COST_OF_SALES => ['name' => 'Cost of sales', 'type' => NominalAccount::TYPE_EXPENSE, 'description' => 'Cost of goods sold.', 'system' => true],
            self::RENT => ['name' => 'Rent & rates', 'type' => NominalAccount::TYPE_EXPENSE, 'description' => 'Depot rent and business rates.', 'system' => false],
            self::WAGES => ['name' => 'Wages & salaries', 'type' => NominalAccount::TYPE_EXPENSE, 'description' => 'Gross payroll.', 'system' => false],
            self::UTILITIES => ['name' => 'Utilities', 'type' => NominalAccount::TYPE_EXPENSE, 'description' => 'Power, water and telecoms.', 'system' => false],
            self::OFFICE => ['name' => 'Office & administration', 'type' => NominalAccount::TYPE_EXPENSE, 'description' => 'Stationery, software and sundries.', 'system' => false],
            self::TRANSPORT => ['name' => 'Motor & transport', 'type' => NominalAccount::TYPE_EXPENSE, 'description' => 'Delivery fleet running costs.', 'system' => false],
            self::BANK_CHARGES => ['name' => 'Bank charges', 'type' => NominalAccount::TYPE_EXPENSE, 'description' => 'Account fees and card charges.', 'system' => false],
        ];
    }

    public static function id(string $code): int
    {
        return (int) NominalAccount::query()->where('code', $code)->value('id');
    }

    /**
     * Create any account in the chart that is missing.
     */
    public static function ensure(): void
    {
        foreach (self::accounts() as $code => $account) {
            NominalAccount::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'description' => $account['description'],
                    'is_system' => $account['system'],
                ],
            );
        }
    }
}
