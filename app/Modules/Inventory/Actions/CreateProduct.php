<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;

class CreateProduct extends Action
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?User $user = null): Product
    {
        $openingStock = (int) ($data['opening_stock'] ?? 0);
        $openingSerials = $data['opening_serials'] ?? [];
        $unitCost = (int) ($data['cost_price_pence'] ?? 0);

        // The request carries decimal strings alongside the pence columns;
        // only the pence values are persisted.
        unset($data['opening_stock'], $data['opening_serials'], $data['cost_price'], $data['sell_price']);

        $product = Product::create($data);

        if ($openingStock > 0) {
            RecordStockMovement::run($product, [
                'type' => StockMovement::TYPE_RECEIPT,
                'quantity' => $openingStock,
                'unit_cost_pence' => $unitCost,
                'reference' => 'Opening stock',
                'note' => 'Opening balance recorded when the product was created.',
                'serials' => $openingSerials,
                'user_id' => $user?->id ?? auth()->id(),
            ]);
        }

        return $product;
    }
}
