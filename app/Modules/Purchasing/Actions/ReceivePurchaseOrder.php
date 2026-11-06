<?php

namespace App\Modules\Purchasing\Actions;

use App\Modules\Accounting\Actions\PostJournalEntry;
use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Actions\RecordStockMovement;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrderLine;
use App\Modules\Purchasing\Models\SupplierBill;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Receiving goods is one operation: stock enters the ledger, the order closes
 * and the supplier bill posts to the nominal ledger with input VAT.
 */
class ReceivePurchaseOrder extends Action
{
    /**
     * @param  array{received_at?: ?string, bill_date?: ?string, reference?: ?string, serials?: array<int, array<int, string>>}  $data
     */
    public function handle(PurchaseOrder $order, array $data = [], ?User $user = null): SupplierBill
    {
        $order->load('lines.product');

        if (! $order->canReceive()) {
            throw ValidationException::withMessages([
                'order' => 'This purchase order has already been received or cancelled.',
            ]);
        }

        $receivedAt = isset($data['received_at']) ? Carbon::parse($data['received_at']) : now();
        $serials = $data['serials'] ?? [];

        foreach ($order->lines as $line) {
            $quantity = $line->outstandingQuantity();

            if ($quantity <= 0) {
                continue;
            }

            RecordStockMovement::run($line->product, [
                'type' => StockMovement::TYPE_RECEIPT,
                'quantity' => $quantity,
                'unit_cost_pence' => $line->unit_cost_pence,
                'reference' => $order->number,
                'note' => 'Goods received against '.$order->number,
                'serials' => $serials[$line->id] ?? [],
                'occurred_at' => $receivedAt->toDateString(),
                'user_id' => $user?->id ?? auth()->id(),
                'source_type' => $order->getMorphClass(),
                'source_id' => $order->id,
            ]);

            $line->update(['quantity_received' => $line->quantity]);
        }

        $order->update([
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'received_at' => $receivedAt->toDateString(),
        ]);

        return $this->createBill($order->fresh('lines', 'supplier'), $receivedAt, $user);
    }

    private function createBill(PurchaseOrder $order, Carbon $receivedAt, ?User $user): SupplierBill
    {
        $dueDate = $receivedAt->copy()->addDays((int) $order->supplier->payment_terms_days);

        $bill = SupplierBill::create([
            'number' => $this->nextBillNumber(),
            'supplier_id' => $order->supplier_id,
            'purchase_order_id' => $order->id,
            'bill_date' => $receivedAt->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'reference' => $order->number,
            'subtotal_pence' => $order->subtotal_pence,
            'vat_pence' => $order->vat_pence,
            'total_pence' => $order->total_pence,
            'amount_paid_pence' => 0,
            'status' => SupplierBill::STATUS_UNPAID,
            'created_by' => $user?->id ?? auth()->id(),
        ]);

        $lines = [
            [
                'account' => ChartOfAccounts::INVENTORY,
                'description' => 'Stock received on '.$order->number,
                'debit_pence' => $bill->subtotal_pence,
            ],
        ];

        if ($bill->vat_pence > 0) {
            $lines[] = [
                'account' => ChartOfAccounts::VAT_RECEIVABLE,
                'description' => 'Input VAT on '.$order->number,
                'debit_pence' => $bill->vat_pence,
            ];
        }

        $lines[] = [
            'account' => ChartOfAccounts::TRADE_CREDITORS,
            'description' => $order->supplier->name,
            'credit_pence' => $bill->total_pence,
        ];

        $journal = PostJournalEntry::run(
            [
                'date' => $receivedAt->toDateString(),
                'description' => 'Supplier bill '.$bill->number.' · '.$order->supplier->name,
            ],
            $lines,
            $bill,
            $user,
        );

        $bill->update(['journal_entry_id' => $journal->id]);

        return $bill->fresh('journalEntry');
    }

    private function nextBillNumber(): string
    {
        $latest = SupplierBill::withTrashed()->orderByDesc('id')->value('number');
        $next = $latest && preg_match('/(\d+)$/', $latest, $matches) ? ((int) $matches[1]) + 1 : 1;

        return config('erp.prefixes.bill', 'BILL').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
