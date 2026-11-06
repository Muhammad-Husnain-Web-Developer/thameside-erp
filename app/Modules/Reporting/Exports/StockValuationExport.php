<?php

namespace App\Modules\Reporting\Exports;

use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockValuationExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    /**
     * @return Collection<int, array<int, mixed>>
     */
    public function collection(): Collection
    {
        return Product::query()
            ->with('category')
            ->withStock()
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                $product->sku,
                $product->name,
                $product->category?->name,
                $product->stockOnHand(),
                $product->unit,
                Money::toDecimal($product->cost_price_pence),
                Money::toDecimal($product->stockValuePence()),
                Money::toDecimal($product->stockOnHand() * $product->sell_price_pence),
                $product->isLowStock() ? 'Yes' : 'No',
            ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'SKU',
            'Product',
            'Category',
            'On hand',
            'Unit',
            'Cost price',
            'Stock at cost',
            'Stock at retail',
            'Low stock',
        ];
    }
}
