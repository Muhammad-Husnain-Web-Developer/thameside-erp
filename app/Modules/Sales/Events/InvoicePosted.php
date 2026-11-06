<?php

namespace App\Modules\Sales\Events;

use App\Modules\Sales\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the moment an invoice is posted. The accounting listener turns it into
 * a balanced journal, so sales code never writes nominal ledger rows itself.
 */
class InvoicePosted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }
}
