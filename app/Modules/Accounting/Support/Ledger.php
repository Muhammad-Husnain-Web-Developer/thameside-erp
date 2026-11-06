<?php

namespace App\Modules\Accounting\Support;

use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\Models\NominalAccount;
use App\Modules\Purchasing\Models\SupplierBill;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-only views over the posted journal. Balances are always derived from
 * journal lines, so there is nothing to reconcile.
 */
final class Ledger
{
    /**
     * @return Collection<string, array{code: string, name: string, type: string, debit: int, credit: int, balance: int}>
     */
    public static function accountBalances(?Carbon $to = null): Collection
    {
        $totals = JournalLine::query()
            ->selectRaw('nominal_account_id, sum(debit_pence) as debit, sum(credit_pence) as credit')
            ->when($to, fn ($query) => $query->whereHas('entry', fn ($entry) => $entry->where('date', '<=', $to)))
            ->groupBy('nominal_account_id')
            ->get()
            ->keyBy('nominal_account_id');

        return NominalAccount::query()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(function (NominalAccount $account) use ($totals) {
                $row = $totals->get($account->id);
                $debit = (int) ($row->debit ?? 0);
                $credit = (int) ($row->credit ?? 0);

                return [$account->code => [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $debit - $credit,
                ]];
            });
    }

    public static function balancePence(string $code, ?Carbon $to = null): int
    {
        return (int) (self::accountBalances($to)->get($code)['balance'] ?? 0);
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, total_debit: int, total_credit: int, balanced: bool}
     */
    public static function trialBalance(?Carbon $to = null): array
    {
        $balances = self::accountBalances($to);

        $rows = $balances->map(fn (array $row) => [
            'code' => $row['code'],
            'name' => $row['name'],
            'type' => NominalAccount::TYPES[$row['type']] ?? ucfirst($row['type']),
            'debit' => $row['balance'] > 0 ? $row['balance'] : 0,
            'credit' => $row['balance'] < 0 ? -$row['balance'] : 0,
        ])->values()->all();

        $totalDebit = (int) array_sum(array_column($rows, 'debit'));
        $totalCredit = (int) array_sum(array_column($rows, 'credit'));

        return [
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'balanced' => $totalDebit === $totalCredit,
        ];
    }

    /**
     * @return array{output_vat: int, input_vat: int, net_due: int, sales_net: int, purchases_net: int}
     */
    public static function vatSummary(Carbon $from, Carbon $to): array
    {
        $lines = JournalLine::query()
            ->whereHas('entry', fn ($query) => $query->whereBetween('date', [$from->toDateString(), $to->toDateString()]))
            ->selectRaw('nominal_account_id, sum(debit_pence) as debit, sum(credit_pence) as credit')
            ->groupBy('nominal_account_id')
            ->get()
            ->keyBy('nominal_account_id');

        $pick = function (string $code) use ($lines): array {
            $id = ChartOfAccounts::id($code);
            $row = $lines->get($id);

            return [(int) ($row->debit ?? 0), (int) ($row->credit ?? 0)];
        };

        [$outputDebit, $outputCredit] = $pick(ChartOfAccounts::VAT_PAYABLE);
        [$inputDebit, $inputCredit] = $pick(ChartOfAccounts::VAT_RECEIVABLE);
        [$salesDebit, $salesCredit] = $pick(ChartOfAccounts::SALES);

        $outputVat = $outputCredit - $outputDebit;
        $inputVat = $inputDebit - $inputCredit;
        $purchasesNet = (int) SupplierBill::query()
            ->whereBetween('bill_date', [$from->toDateString(), $to->toDateString()])
            ->sum('subtotal_pence');

        return [
            'output_vat' => $outputVat,
            'input_vat' => $inputVat,
            'net_due' => $outputVat - $inputVat,
            'sales_net' => $salesCredit - $salesDebit,
            'purchases_net' => $purchasesNet,
        ];
    }

    /**
     * @return array{gross_profit: int, net_profit: int, income: int, cost_of_sales: int, expenses: int}
     */
    public static function profitAndLoss(?Carbon $to = null): array
    {
        $balances = self::accountBalances($to);

        $income = 0;
        $costOfSales = 0;
        $expenses = 0;

        foreach ($balances as $row) {
            if ($row['type'] === NominalAccount::TYPE_INCOME) {
                $income += -$row['balance'];
            }

            if ($row['type'] === NominalAccount::TYPE_EXPENSE) {
                $row['code'] === ChartOfAccounts::COST_OF_SALES
                    ? $costOfSales += $row['balance']
                    : $expenses += $row['balance'];
            }
        }

        return [
            'income' => $income,
            'cost_of_sales' => $costOfSales,
            'expenses' => $expenses,
            'gross_profit' => $income - $costOfSales,
            'net_profit' => $income - $costOfSales - $expenses,
        ];
    }
}
