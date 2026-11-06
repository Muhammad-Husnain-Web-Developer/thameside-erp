<?php

namespace App\Modules\Ai\Services;

use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Read-only forecasting. Demand is the average monthly units sold over the
 * last six months of stock movements; nothing here writes to the ledger.
 */
class DemandForecaster
{
    private const LOOKBACK_MONTHS = 6;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function suggestions(int $limit = 8): array
    {
        $start = now()->subMonths(self::LOOKBACK_MONTHS)->startOfMonth();

        $sold = DB::table('stock_movements')
            ->where('type', 'sale')
            ->where('occurred_at', '>=', $start->toDateString())
            ->groupBy('product_id')
            ->selectRaw('product_id, sum(-quantity) as units')
            ->pluck('units', 'product_id');

        return Product::query()
            ->where('is_active', true)
            ->withStock()
            ->get()
            ->map(function (Product $product) use ($sold) {
                $units = (int) ($sold[$product->id] ?? 0);
                $monthly = $units / self::LOOKBACK_MONTHS;
                $onHand = $product->stockOnHand();

                $cover = $monthly > 0 ? round($onHand / $monthly, 1) : null;
                $target = (int) ceil($monthly * 3) + $product->reorder_level;
                $suggested = max(0, $target - $onHand);

                return [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'on_hand' => $onHand,
                    'reorder_level' => $product->reorder_level,
                    'monthly_demand' => round($monthly, 1),
                    'months_cover' => $cover,
                    'suggested_order' => $suggested,
                    'suggested_cost' => Money::format($suggested * $product->cost_price_pence),
                    'reason' => $cover === null
                        ? 'No sales in the last six months'
                        : ($cover < 1 ? 'Less than a month of cover left' : 'Below three months of cover'),
                    'urgent' => $onHand <= $product->reorder_level || ($cover !== null && $cover < 1),
                ];
            })
            ->filter(fn (array $row) => $row['suggested_order'] > 0)
            ->sortBy(fn (array $row) => [$row['months_cover'] ?? 999, -$row['monthly_demand']])
            ->take($limit)
            ->values()
            ->all();
    }
}
