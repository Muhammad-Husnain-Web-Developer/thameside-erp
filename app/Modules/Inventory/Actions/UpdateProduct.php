<?php

namespace App\Modules\Inventory\Actions;

use App\Modules\Core\Actions\Action;
use App\Modules\Inventory\Models\Product;

class UpdateProduct extends Action
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Product $product, array $data): Product
    {
        // A serial-tracked product cannot switch tracking off while units with
        // serial numbers are still on the shelf.
        if ($product->tracks_serial && array_key_exists('tracks_serial', $data) && ! $data['tracks_serial']) {
            if ($product->serials()->where('status', 'in_stock')->exists()) {
                $data['tracks_serial'] = true;
            }
        }

        unset($data['cost_price'], $data['sell_price']);

        $product->update($data);

        return $product->refresh();
    }
}
