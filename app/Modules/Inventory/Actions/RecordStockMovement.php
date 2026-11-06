<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductSerial;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * The only way stock can change. Nothing in the application writes a quantity
 * directly: every receipt, sale, return, adjustment and write-off is a ledger
 * row, and stock on hand is the sum of those rows.
 */
class RecordStockMovement extends Action
{
    /**
     * @param  array{type: string, quantity: int|string, unit_cost_pence?: ?int, reference?: ?string, note?: ?string, serials?: array<int, string>, occurred_at?: ?string, user_id?: ?int, source_type?: ?string, source_id?: ?int}  $data
     */
    public function handle(Product $product, array $data): StockMovement
    {
        $type = (string) ($data['type'] ?? StockMovement::TYPE_ADJUSTMENT);
        $input = (int) ($data['quantity'] ?? 0);
        $serials = $this->normaliseSerials($data['serials'] ?? []);

        $quantity = $this->signedQuantity($type, $input);
        $onHand = $product->stockOnHand();

        if ($quantity < 0 && $onHand + $quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$onHand} unit(s) of {$product->name} are in stock.",
            ]);
        }

        if ($product->tracks_serial) {
            $this->guardSerials($product, $type, $quantity, $serials);
        }

        $movement = $product->stockMovements()->create([
            'type' => $type,
            'quantity' => $quantity,
            'unit_cost_pence' => $data['unit_cost_pence'] ?? $product->cost_price_pence,
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'occurred_at' => isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now(),
        ]);

        if ($product->tracks_serial) {
            $this->applySerials($product, $movement, $type, $serials);
        }

        return $movement->refresh();
    }

    private function signedQuantity(string $type, int $input): int
    {
        if (! array_key_exists($type, StockMovement::TYPES)) {
            throw ValidationException::withMessages([
                'type' => 'Unknown stock movement type.',
            ]);
        }

        if ($input === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantity must not be zero.',
            ]);
        }

        if ($type === StockMovement::TYPE_ADJUSTMENT) {
            return $input;
        }

        $magnitude = abs($input);

        return in_array($type, StockMovement::INBOUND, true) ? $magnitude : -$magnitude;
    }

    /**
     * @param  array<int, string>  $serials
     */
    private function guardSerials(Product $product, string $type, int $quantity, array $serials): void
    {
        if ($type === StockMovement::TYPE_ADJUSTMENT) {
            throw ValidationException::withMessages([
                'serials' => 'Serial-tracked products are adjusted by receiving, selling or writing off specific units.',
            ]);
        }

        if (count($serials) !== abs($quantity)) {
            throw ValidationException::withMessages([
                'serials' => 'Provide exactly '.abs($quantity).' serial number(s) for this movement.',
            ]);
        }

        if (count(array_unique($serials)) !== count($serials)) {
            throw ValidationException::withMessages([
                'serials' => 'Serial numbers must be unique within a movement.',
            ]);
        }

        if (in_array($type, StockMovement::INBOUND, true)) {
            $existing = ProductSerial::query()
                ->where('product_id', $product->id)
                ->whereIn('serial_number', $serials)
                ->pluck('serial_number')
                ->all();

            if ($existing !== []) {
                throw ValidationException::withMessages([
                    'serials' => 'Already on file: '.implode(', ', $existing).'.',
                ]);
            }

            return;
        }

        $available = ProductSerial::query()
            ->where('product_id', $product->id)
            ->where('status', ProductSerial::STATUS_IN_STOCK)
            ->whereIn('serial_number', $serials)
            ->pluck('serial_number')
            ->all();

        $missing = array_values(array_diff($serials, $available));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'serials' => 'Not in stock: '.implode(', ', $missing).'.',
            ]);
        }
    }

    /**
     * @param  array<int, string>  $serials
     */
    private function applySerials(Product $product, StockMovement $movement, string $type, array $serials): void
    {
        if (in_array($type, StockMovement::INBOUND, true)) {
            foreach ($serials as $serial) {
                ProductSerial::create([
                    'product_id' => $product->id,
                    'serial_number' => $serial,
                    'status' => ProductSerial::STATUS_IN_STOCK,
                    'stock_movement_id' => $movement->id,
                    'received_at' => $movement->occurred_at,
                ]);
            }

            return;
        }

        $status = $type === StockMovement::TYPE_WRITE_OFF
            ? ProductSerial::STATUS_WRITTEN_OFF
            : ProductSerial::STATUS_SOLD;

        ProductSerial::query()
            ->where('product_id', $product->id)
            ->whereIn('serial_number', $serials)
            ->update([
                'status' => $status,
                'stock_movement_id' => $movement->id,
                'sold_at' => $movement->occurred_at,
            ]);
    }

    /**
     * @param  array<int, mixed>  $serials
     * @return array<int, string>
     */
    private function normaliseSerials(array $serials): array
    {
        return array_values(array_filter(array_map(
            fn ($serial) => trim((string) $serial),
            $serials,
        ), fn (string $serial) => $serial !== ''));
    }
}
