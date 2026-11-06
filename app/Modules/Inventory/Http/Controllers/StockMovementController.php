<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Actions\RecordStockMovement;
use App\Modules\Inventory\Http\Requests\StoreStockMovementRequest;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StockMovement::class);

        $type = (string) $request->string('type');
        $productId = $request->integer('product') ?: null;

        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'type' => $movement->type,
                'type_label' => $movement->typeLabel(),
                'product' => $movement->product?->name,
                'sku' => $movement->product?->sku,
                'product_id' => $movement->product_id,
                'quantity' => $movement->quantity,
                'signed_quantity' => $movement->signedQuantity(),
                'reference' => $movement->reference,
                'note' => $movement->note,
                'user' => $movement->user?->name ?? 'System',
                'occurred_at' => $movement->occurred_at?->format('j M Y, H:i'),
                'value' => Money::format($movement->valuePence()),
            ]);

        return Inertia::render('Inventory/Movements/Index', [
            'movements' => $movements,
            'products' => Product::query()
                ->orderBy('name')
                ->get(['id', 'sku', 'name', 'tracks_serial'])
                ->map(fn (Product $product) => [
                    'value' => $product->id,
                    'label' => "{$product->sku} · {$product->name}",
                    'tracks_serial' => $product->tracks_serial,
                ]),
            'types' => StockMovement::TYPES,
            'filters' => [
                'type' => $type,
                'product' => $productId,
            ],
            'stats' => $this->stats(),
            'can' => [
                'adjust' => $request->user()->can('create', StockMovement::class),
            ],
        ]);
    }

    public function store(StoreStockMovementRequest $request): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));

        $movement = RecordStockMovement::run($product, [
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $movement->load('product');

        return redirect()
            ->route('inventory.stock.index')
            ->with('success', sprintf(
                '%s %d × %s recorded. Stock on hand is now %d.',
                $movement->typeLabel(),
                abs($movement->quantity),
                $product->name,
                $product->stockOnHand(),
            ));
    }

    /**
     * @return array<int, array{key: string, label: string, value: string|int, meta: string, icon: string}>
     */
    private function stats(): array
    {
        $since = now()->startOfMonth();

        $in = (int) StockMovement::query()->where('quantity', '>', 0)->where('occurred_at', '>=', $since)->sum('quantity');
        $out = (int) StockMovement::query()->where('quantity', '<', 0)->where('occurred_at', '>=', $since)->sum('quantity');

        return [
            [
                'key' => 'in',
                'label' => 'Units received',
                'value' => number_format($in),
                'meta' => 'This month, across all products',
                'icon' => 'truck',
            ],
            [
                'key' => 'out',
                'label' => 'Units despatched',
                'value' => number_format(abs($out)),
                'meta' => 'Sales, returns and write-offs',
                'icon' => 'box',
            ],
            [
                'key' => 'movements',
                'label' => 'Ledger entries',
                'value' => number_format(StockMovement::query()->count()),
                'meta' => 'Every movement is permanent',
                'icon' => 'history',
            ],
            [
                'key' => 'value',
                'label' => 'Stock at cost',
                'value' => Money::format($this->stockValue()),
                'meta' => 'Current valuation',
                'icon' => 'banknotes',
            ],
        ];
    }

    private function stockValue(): int
    {
        return (int) DB::query()
            ->fromSub(
                DB::table('stock_movements')
                    ->selectRaw('product_id, sum(quantity) as quantity')
                    ->groupBy('product_id'),
                'totals',
            )
            ->join('products', 'products.id', '=', 'totals.product_id')
            ->sum(DB::raw('totals.quantity * products.cost_price_pence'));
    }
}
