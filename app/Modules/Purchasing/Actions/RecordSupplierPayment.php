<?php

namespace App\Modules\Purchasing\Actions;

use App\Modules\Accounting\Actions\PostJournalEntry;
use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\Money;
use App\Modules\Purchasing\Models\SupplierBill;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RecordSupplierPayment extends Action
{
    /**
     * @param  array{amount?: mixed, amount_pence?: mixed, paid_at?: ?string, reference?: ?string}  $data
     */
    public function handle(SupplierBill $bill, array $data, ?User $user = null): SupplierBill
    {
        $amount = isset($data['amount_pence'])
            ? (int) $data['amount_pence']
            : Money::parse($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount to pay.']);
        }

        if ($amount > $bill->outstandingPence()) {
            throw ValidationException::withMessages([
                'amount' => 'That is more than the '.Money::format($bill->outstandingPence()).' still outstanding.',
            ]);
        }

        $paidAt = isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : now();
        $paid = $bill->amount_paid_pence + $amount;

        PostJournalEntry::run(
            [
                'date' => $paidAt->toDateString(),
                'description' => 'Payment to '.$bill->supplier->name.' · '.$bill->number,
            ],
            [
                [
                    'account' => ChartOfAccounts::TRADE_CREDITORS,
                    'description' => $bill->number,
                    'debit_pence' => $amount,
                ],
                [
                    'account' => ChartOfAccounts::BANK,
                    'description' => $data['reference'] ?? 'BACS payment',
                    'credit_pence' => $amount,
                ],
            ],
            $bill,
            $user,
        );

        $bill->update([
            'amount_paid_pence' => $paid,
            'status' => $paid >= $bill->total_pence ? SupplierBill::STATUS_PAID : SupplierBill::STATUS_PART_PAID,
        ]);

        return $bill->refresh();
    }
}
