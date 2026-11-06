<?php

namespace App\Modules\Sales\Listeners;

use App\Modules\Accounting\Actions\PostJournalEntry;
use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Sales\Events\InvoicePosted;

class PostInvoiceJournal
{
    public function handle(InvoicePosted $event): void
    {
        $invoice = $event->invoice->loadMissing('customer');

        $lines = [
            [
                'account' => ChartOfAccounts::TRADE_DEBTORS,
                'description' => $invoice->customer?->name,
                'debit_pence' => $invoice->total_pence,
            ],
            [
                'account' => ChartOfAccounts::SALES,
                'description' => 'Sales on '.$invoice->number,
                'credit_pence' => $invoice->subtotal_pence,
            ],
        ];

        if ($invoice->vat_pence > 0) {
            $lines[] = [
                'account' => ChartOfAccounts::VAT_PAYABLE,
                'description' => 'Output VAT on '.$invoice->number,
                'credit_pence' => $invoice->vat_pence,
            ];
        }

        $journal = PostJournalEntry::run(
            [
                'date' => $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
                'description' => 'Sales invoice '.$invoice->number.' · '.$invoice->customer?->name,
            ],
            $lines,
            $invoice,
        );

        $invoice->update(['journal_entry_id' => $journal->id]);
    }
}
