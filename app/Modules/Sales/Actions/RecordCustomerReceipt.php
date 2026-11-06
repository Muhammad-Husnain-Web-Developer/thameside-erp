<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Accounting\Actions\PostJournalEntry;
use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\Money;
use App\Modules\Sales\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class RecordCustomerReceipt extends Action
{
    /**
     * @param  array{amount?: mixed, amount_pence?: mixed, received_at?: ?string, reference?: ?string}  $data
     */
    public function handle(Invoice $invoice, array $data, ?User $user = null): Invoice
    {
        $amount = isset($data['amount_pence'])
            ? (int) $data['amount_pence']
            : Money::parse($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount received.']);
        }

        if ($amount > $invoice->outstandingPence()) {
            throw ValidationException::withMessages([
                'amount' => 'That is more than the '.Money::format($invoice->outstandingPence()).' still outstanding.',
            ]);
        }

        $receivedAt = isset($data['received_at']) ? Carbon::parse($data['received_at']) : now();
        $paid = $invoice->amount_paid_pence + $amount;

        PostJournalEntry::run(
            [
                'date' => $receivedAt->toDateString(),
                'description' => 'Receipt from '.$invoice->customer->name.' · '.$invoice->number,
            ],
            [
                [
                    'account' => ChartOfAccounts::BANK,
                    'description' => $data['reference'] ?? 'Customer receipt',
                    'debit_pence' => $amount,
                ],
                [
                    'account' => ChartOfAccounts::TRADE_DEBTORS,
                    'description' => $invoice->number,
                    'credit_pence' => $amount,
                ],
            ],
            $invoice,
            $user,
        );

        $invoice->update([
            'amount_paid_pence' => $paid,
            'status' => $paid >= $invoice->total_pence ? Invoice::STATUS_PAID : Invoice::STATUS_PART_PAID,
        ]);

        return $invoice->refresh();
    }
}
