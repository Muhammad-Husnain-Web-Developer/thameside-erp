<?php

namespace App\Modules\Purchasing\Actions;

use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\Money;
use App\Modules\Core\Support\Vat;
use App\Modules\Inventory\Models\Product;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Validation\ValidationException;

class CreatePurchaseOrder extends Action
{
    /**
     * @param  array{supplier_id: int|string, ordered_at?: ?string, expected_at?: ?string, notes?: ?string, lines: array<int, array{product_id?: int|string, description?: ?string, quantity?: mixed, unit_cost?: mixed, unit_cost_pence?: mixed, vat_rate_bps?: mixed}>}  $data
     */
    public function handle(array $data, ?User $user = null): PurchaseOrder
    {
        $supplier = Supplier::query()->find($data['supplier_id'] ?? null);

        if (! $supplier) {
            throw ValidationException::withMessages(['supplier_id' => 'Choose a supplier for this order.']);
        }

        $lines = $this->prepareLines($data['lines'] ?? []);

        $subtotal = array_sum(array_column($lines, 'line_total_pence'));
        $vat = array_sum(array_map(
            fn (array $line) => (int) round($line['line_total_pence'] * $line['vat_rate_bps'] / 10000),
            $lines,
        ));

        $order = PurchaseOrder::create([
            'number' => $this->nextNumber(),
            'supplier_id' => $supplier->id,
            'ordered_at' => $data['ordered_at'] ?? now()->toDateString(),
            'expected_at' => $data['expected_at'] ?? null,
            'status' => PurchaseOrder::STATUS_ORDERED,
            'subtotal_pence' => $subtotal,
            'vat_pence' => $vat,
            'total_pence' => $subtotal + $vat,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user?->id ?? auth()->id(),
        ]);

        foreach ($lines as $line) {
            $order->lines()->create($line);
        }

        return $order->load('lines.product', 'supplier');
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function prepareLines(array $lines): array
    {
        $prepared = [];

        foreach ($lines as $index => $line) {
            $product = Product::query()->find($line['product_id'] ?? null);

            if (! $product) {
                throw ValidationException::withMessages([
                    "lines.{$index}.product_id" => 'Choose a product for every line.',
                ]);
            }

            $quantity = (int) ($line['quantity'] ?? 0);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.quantity" => 'Quantity must be at least one.',
                ]);
            }

            $unitCost = isset($line['unit_cost_pence'])
                ? (int) $line['unit_cost_pence']
                : Money::parse($line['unit_cost'] ?? $product->cost_price_pence / 100);

            if ($unitCost <= 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.unit_cost" => 'Enter a unit cost.',
                ]);
            }

            $rateBps = isset($line['vat_rate_bps']) && $line['vat_rate_bps'] !== ''
                ? (int) $line['vat_rate_bps']
                : $product->vat_rate_bps;

            $lineTotal = $unitCost * $quantity;

            $prepared[] = [
                'product_id' => $product->id,
                'description' => $line['description'] ?? $product->name,
                'quantity' => $quantity,
                'quantity_received' => 0,
                'unit_cost_pence' => $unitCost,
                'vat_rate_bps' => $rateBps,
                'line_total_pence' => $lineTotal,
            ];
        }

        if ($prepared === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one product to the order.']);
        }

        return $prepared;
    }

    private function nextNumber(): string
    {
        $latest = PurchaseOrder::withTrashed()->orderByDesc('id')->value('number');
        $next = $latest && preg_match('/(\d+)$/', $latest, $matches) ? ((int) $matches[1]) + 1 : 1;

        return config('erp.prefixes.purchase_order', 'PO').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
