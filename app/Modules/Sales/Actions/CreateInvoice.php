<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Actions\RecordStockMovement;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Sales\Events\InvoicePosted;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Invoicing an order posts the sale: stock leaves the ledger, the invoice is
 * created and the InvoicePosted event writes the double entry. If stock is
 * short, the whole transaction rolls back.
 */
class CreateInvoice extends Action
{
    /**
     * @param  array{invoice_date?: ?string, due_date?: ?string, notes?: ?string, serials?: array<int, array<int, string>>}  $data
     */
    public function handle(SalesOrder $order, array $data = [], ?User $user = null): Invoice
    {
        $order->load('lines.product', 'customer');

        if (! $order->canInvoice()) {
            throw ValidationException::withMessages([
                'order' => 'This order has already been invoiced or cancelled.',
            ]);
        }

        $invoiceDate = isset($data['invoice_date']) ? Carbon::parse($data['invoice_date']) : now();
        $dueDate = isset($data['due_date'])
            ? Carbon::parse($data['due_date'])
            : $invoiceDate->copy()->addDays((int) $order->customer->payment_terms_days);

        $serials = $data['serials'] ?? [];

        $invoice = Invoice::create([
            'number' => $this->nextNumber($invoiceDate),
            'customer_id' => $order->customer_id,
            'sales_order_id' => $order->id,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'subtotal_pence' => $order->subtotal_pence,
            'vat_pence' => $order->vat_pence,
            'total_pence' => $order->total_pence,
            'amount_paid_pence' => 0,
            'status' => Invoice::STATUS_POSTED,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user?->id ?? auth()->id(),
        ]);

        foreach ($order->lines as $line) {
            $invoice->lines()->create([
                'product_id' => $line->product_id,
                'description' => $line->description ?? $line->product?->name ?? 'Item',
                'quantity' => $line->quantity,
                'unit_price_pence' => $line->unit_price_pence,
                'vat_rate_bps' => $line->vat_rate_bps,
                'line_total_pence' => $line->line_total_pence,
            ]);

            RecordStockMovement::run($line->product, [
                'type' => StockMovement::TYPE_SALE,
                'quantity' => $line->quantity,
                'unit_cost_pence' => $line->product->cost_price_pence,
                'reference' => $invoice->number,
                'note' => 'Sold on '.$invoice->number,
                'serials' => $serials[$line->id] ?? [],
                'occurred_at' => $invoiceDate->toDateString(),
                'user_id' => $user?->id ?? auth()->id(),
                'source_type' => $invoice->getMorphClass(),
                'source_id' => $invoice->id,
            ]);

            $line->update(['quantity_fulfilled' => $line->quantity]);
        }

        event(new InvoicePosted($invoice));

        $order->update([
            'status' => SalesOrder::STATUS_FULFILLED,
            'fulfilled_at' => $invoiceDate->toDateString(),
        ]);

        return $invoice->fresh(['lines', 'journalEntry', 'customer']);
    }

    private function nextNumber(Carbon $date): string
    {
        $latest = Invoice::withTrashed()->orderByDesc('id')->value('number');
        $next = $latest && preg_match('/(\d+)$/', $latest, $matches) ? ((int) $matches[1]) + 1 : 1;

        return config('erp.prefixes.invoice', 'INV').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
