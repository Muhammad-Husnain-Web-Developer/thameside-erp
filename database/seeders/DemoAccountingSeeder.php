<?php

namespace Database\Seeders;

use App\Modules\Accounting\Actions\PostJournalEntry;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Support\ChartOfAccounts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Opening balances for the demo company plus a month of running costs, so the
 * trial balance, P&L and VAT return have something to show.
 */
class DemoAccountingSeeder extends Seeder
{
    public function run(): void
    {
        ChartOfAccounts::ensure();

        if (JournalEntry::query()->exists()) {
            return;
        }

        $stockValue = (int) DB::query()
            ->fromSub(
                DB::table('stock_movements')
                    ->selectRaw('product_id, sum(quantity) as quantity')
                    ->groupBy('product_id'),
                'totals',
            )
            ->join('products', 'products.id', '=', 'totals.product_id')
            ->sum(DB::raw('totals.quantity * products.cost_price_pence'));

        $bank = 8_500_000;
        $capital = 4_000_000;

        PostJournalEntry::run(
            [
                'date' => now()->startOfYear()->toDateString(),
                'description' => 'Opening balances brought forward',
            ],
            [
                ['account' => ChartOfAccounts::BANK, 'description' => 'Lloyds current account', 'debit_pence' => $bank],
                ['account' => ChartOfAccounts::INVENTORY, 'description' => 'Depot stock at cost', 'debit_pence' => $stockValue],
                ['account' => ChartOfAccounts::SHARE_CAPITAL, 'description' => 'Issued share capital', 'credit_pence' => $capital],
                ['account' => ChartOfAccounts::RETAINED_EARNINGS, 'description' => 'Accumulated reserves', 'credit_pence' => $bank + $stockValue - $capital],
            ],
        );

        $month = now()->startOfMonth();

        PostJournalEntry::run(
            [
                'date' => $month->copy()->addDays(2)->toDateString(),
                'description' => 'Monthly depot running costs',
            ],
            [
                ['account' => ChartOfAccounts::RENT, 'description' => 'Unit 4 Thameside Way', 'debit_pence' => 325_000],
                ['account' => ChartOfAccounts::UTILITIES, 'description' => 'Power and water', 'debit_pence' => 68_400],
                ['account' => ChartOfAccounts::BANK, 'description' => 'Direct debits', 'credit_pence' => 393_400],
            ],
        );

        PostJournalEntry::run(
            [
                'date' => $month->copy()->addDays(5)->toDateString(),
                'description' => 'Delivery van fuel and servicing',
            ],
            [
                ['account' => ChartOfAccounts::TRANSPORT, 'description' => 'Fleet running costs', 'debit_pence' => 41_200],
                ['account' => ChartOfAccounts::BANK_CHARGES, 'description' => 'Card processing fees', 'debit_pence' => 9_600],
                ['account' => ChartOfAccounts::BANK, 'description' => 'Paid from current account', 'credit_pence' => 50_800],
            ],
        );
    }
}
