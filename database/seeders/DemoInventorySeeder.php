<?php

namespace Database\Seeders;

use App\Modules\Core\Models\User;
use App\Modules\Inventory\Actions\RecordStockMovement;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Database\Seeder;

class DemoInventorySeeder extends Seeder
{
    /**
     * A believable kitchen appliance catalogue with opening stock posted
     * through the movement ledger (never written as a quantity).
     */
    public function run(): void
    {
        if (Product::query()->exists()) {
            return;
        }

        $categories = collect([
            'Range cookers' => 'Freestanding and built-in cooking ranges.',
            'Extractor hoods' => 'Canopy, chimney and integrated extraction.',
            'Refrigeration' => 'Fridges, freezers and wine coolers.',
            'Dishwashers' => 'Freestanding and fully integrated models.',
            'Sinks & taps' => 'Stainless steel sinks, mixer taps and wastes.',
            'Laundry' => 'Washers, dryers and laundry cabinets.',
            'Small appliances' => 'Countertop lines stocked for trade bundles.',
        ])->mapWithKeys(fn (string $description, string $name) => [
            $name => ProductCategory::create(['name' => $name, 'description' => $description]),
        ]);

        $admin = User::query()->where('email', 'amelia.hart@thamesidekitchen.co.uk')->first()
            ?? User::query()->first();

        // [category, brand, name, cost pence, sell pence, reorder level, tracks serial, opening units]
        $catalogue = [
            ['Range cookers', 'Beaumont', '90cm dual fuel range cooker', 118000, 174900, 3, true, 4],
            ['Range cookers', 'Beaumont', '110cm induction range cooker', 149500, 219900, 2, true, 2],
            ['Range cookers', 'Calder', '60cm ceramic range cooker', 74500, 112500, 4, true, 5],
            ['Extractor hoods', 'Marlow', 'Chimney extractor hood, 90cm', 18500, 29900, 12, false, 14],
            ['Extractor hoods', 'Marlow', 'Integrated extractor, 60cm', 12900, 21500, 10, false, 9],
            ['Refrigeration', 'Ashford', 'American fridge freezer, plumbed', 92000, 138500, 3, true, 3],
            ['Refrigeration', 'Ashford', 'Under-counter larder fridge', 24500, 39900, 15, false, 18],
            ['Refrigeration', 'Thornbury', 'Dual-zone wine cooler, 38 bottle', 31500, 48900, 4, true, 4],
            ['Dishwashers', 'Calder', 'Freestanding dishwasher, 14 place', 28900, 44500, 8, false, 11],
            ['Dishwashers', 'Calder', 'Fully integrated dishwasher', 30500, 47900, 6, false, 7],
            ['Sinks & taps', 'Wren', 'Brushed steel 1.5 bowl sink', 6200, 11500, 20, false, 24],
            ['Sinks & taps', 'Wren', 'Pull-out mixer tap, brushed nickel', 4800, 8900, 25, false, 31],
            ['Laundry', 'Ashford', '9kg heat pump dryer', 39900, 59500, 4, true, 5],
            ['Laundry', 'Ashford', 'Integrated washing machine, 8kg', 27500, 41900, 6, false, 8],
            ['Small appliances', 'Marlow', '4-slice toaster, stainless', 2100, 3900, 40, false, 46],
            ['Small appliances', 'Marlow', '1.7L pyramid kettle', 1850, 3400, 45, false, 52],
        ];

        $index = 1001;

        foreach ($catalogue as [$category, $brand, $name, $cost, $sell, $reorder, $tracksSerial, $units]) {
            $product = Product::create([
                'sku' => 'TKS-'.$index++,
                'name' => $name,
                'description' => "{$brand} {$name}, supplied with a two-year parts and labour warranty.",
                'product_category_id' => $categories[$category]->id,
                'brand' => $brand,
                'unit' => 'each',
                'cost_price_pence' => $cost,
                'sell_price_pence' => $sell,
                'vat_rate_bps' => 2000,
                'tracks_serial' => $tracksSerial,
                'reorder_level' => $reorder,
                'is_active' => true,
            ]);

            if ($units <= 0) {
                continue;
            }

            $serials = [];

            if ($tracksSerial) {
                $prefix = strtoupper(substr($brand, 0, 3)).'-'
                    .strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 4));

                for ($unit = 1; $unit <= $units; $unit++) {
                    $serials[] = sprintf('%s-%04d', $prefix, $unit);
                }
            }

            RecordStockMovement::run($product, [
                'type' => StockMovement::TYPE_RECEIPT,
                'quantity' => $units,
                'unit_cost_pence' => $cost,
                'reference' => 'Opening stocktake',
                'note' => 'Demo opening balance.',
                'serials' => $serials,
                'user_id' => $admin?->id,
            ]);
        }
    }
}
