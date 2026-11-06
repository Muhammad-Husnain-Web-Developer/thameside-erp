<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Validation\ValidationException;

class CreateSalesOrder extends Action
{
    /**
     * @param  array{customer_id: int|string, ordered_at?: ?string, notes?: ?string, lines: array<int, array{product_id?: int|string, description?: ?string, quantity?: mixed, unit_price?: mixed, unit_price_pence?: mixed}>}  $data
     */
    public function handle(array $data, ?User $user = null): SalesOrder
    {
        $customer = Customer::query()->find($data['customer_id'] ?? null);

        if (! $customer) {
            throw ValidationException::withMessages(['customer_id' => 'Choose a customer for this order.']);
        }

        $lines = $this->prepareLines($data['lines'] ?? []);

        $subtotal = array_sum(array_column($lines, 'line_total_pence'));
        $vat = array_sum(array_map(
            fn (array $line) => (int) round($line['line_total_pence'] * $line['vat_rate_bps'] / 10000),
            $lines,
        ));

        $order = SalesOrder::create([
            'number' => $this->nextNumber(),
            'customer_id' => $customer->id,
            'ordered_at' => $data['ordered_at'] ?? now()->toDateString(),
            'status' => SalesOrder::STATUS_CONFIRMED,
            'subtotal_pence' => $subtotal,
            'vat_pence' => $vat,
            'total_pence' => $subtotal + $vat,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user?->id ?? auth()->id(),
        ]);

        foreach ($lines as $line) {
            $order->lines()->create($line);
        }

        return $order->load('lines.product', 'customer');
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

            $unitPrice = isset($line['unit_price_pence'])
                ? (int) $line['unit_price_pence']
                : Money::parse($line['unit_price'] ?? $product->sell_price_pence / 100);

            if ($unitPrice <= 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.unit_price" => 'Enter a selling price.',
                ]);
            }

            $prepared[] = [
                'product_id' => $product->id,
                'description' => $line['description'] ?? $product->name,
                'quantity' => $quantity,
                'quantity_fulfilled' => 0,
                'unit_price_pence' => $unitPrice,
                'vat_rate_bps' => $product->vat_rate_bps,
                'line_total_pence' => $unitPrice * $quantity,
            ];
        }

        if ($prepared === []) {
            throw ValidationException::withMessages(['lines' => 'Add at least one product to the order.']);
        }

        return $prepared;
    }

    private function nextNumber(): string
    {
        $latest = SalesOrder::withTrashed()->orderByDesc('id')->value('number');
        $next = $latest && preg_match('/(\d+)$/', $latest, $matches) ? ((int) $matches[1]) + 1 : 1;

        return config('erp.prefixes.sales_order', 'SO').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
