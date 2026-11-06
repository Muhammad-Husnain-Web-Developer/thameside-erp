<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Actions\CreateProduct;
use App\Modules\Inventory\Actions\UpdateProduct;
use App\Modules\Inventory\Http\Requests\StoreProductRequest;
use App\Modules\Inventory\Http\Requests\UpdateProductRequest;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $search = trim((string) $request->string('q'));
        $category = $request->integer('category') ?: null;
        $stock = (string) $request->string('stock');

        $products = Product::query()
            ->with('category')
            ->withStock()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            }))
            ->when($category, fn ($query) => $query->where('product_category_id', $category))
            ->when($stock === 'low', fn ($query) => $query->lowStock())
            ->when($stock === 'out', fn ($query) => $query->whereRaw(
                '(select coalesce(sum(quantity), 0) from stock_movements where stock_movements.product_id = products.id) <= 0'
            ))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'brand' => $product->brand,
                'category' => $product->category?->name,
                'unit' => $product->unit,
                'stock_on_hand' => $product->stockOnHand(),
                'reorder_level' => $product->reorder_level,
                'is_low' => $product->isLowStock(),
                'tracks_serial' => $product->tracks_serial,
                'sell_price' => Money::format($product->sell_price_pence),
                'cost_price' => Money::format($product->cost_price_pence),
                'margin' => $product->marginPercent(),
                'is_active' => $product->is_active,
                'can' => [
                    'update' => $request->user()->can('update', $product),
                    'delete' => $request->user()->can('delete', $product),
                ],
            ]);

        return Inertia::render('Inventory/Products/Index', [
            'products' => $products,
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'q' => $search,
                'category' => $category,
                'stock' => $stock,
            ],
            'stats' => $this->stats(),
            'can' => [
                'create' => $request->user()->can('create', Product::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Inventory/Products/Create', [
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
            'suggestedSku' => $this->nextSku(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = CreateProduct::run($request->validated(), $request->user());

        return redirect()
            ->route('inventory.products.show', $product)
            ->with('success', "{$product->name} was added to the catalogue.");
    }

    public function show(Request $request, Product $product): Response
    {
        $this->authorize('view', $product);

        $product->load('category');

        $movements = $product->stockMovements()
            ->with('user')
            ->latest('occurred_at')
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'type' => $movement->type,
                'type_label' => $movement->typeLabel(),
                'quantity' => $movement->quantity,
                'signed_quantity' => $movement->signedQuantity(),
                'reference' => $movement->reference,
                'note' => $movement->note,
                'user' => $movement->user?->name,
                'occurred_at' => $movement->occurred_at?->format('j M Y, H:i'),
                'value' => $movement->displayValue(),
            ]);

        $serials = $product->serials()
            ->orderByRaw("case when status = 'in_stock' then 0 else 1 end")
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn ($serial) => [
                'id' => $serial->id,
                'serial_number' => $serial->serial_number,
                'status' => $serial->status,
                'received_at' => $serial->received_at?->format('j M Y'),
                'sold_at' => $serial->sold_at?->format('j M Y'),
            ]);

        return Inertia::render('Inventory/Products/Show', [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'description' => $product->description,
                'brand' => $product->brand,
                'unit' => $product->unit,
                'category' => $product->category?->name,
                'cost_price' => Money::format($product->cost_price_pence),
                'sell_price' => Money::format($product->sell_price_pence),
                'margin' => $product->marginPercent(),
                'vat_rate' => $product->vat_rate_bps / 100,
                'tracks_serial' => $product->tracks_serial,
                'reorder_level' => $product->reorder_level,
                'is_active' => $product->is_active,
            ],
            'stock' => [
                'on_hand' => $product->stockOnHand(),
                'reorder_level' => $product->reorder_level,
                'is_low' => $product->isLowStock(),
                'value' => Money::format($product->stockValuePence()),
                'retail_value' => Money::format($product->stockOnHand() * $product->sell_price_pence),
                'serials_in_stock' => $product->tracks_serial
                    ? $product->serials()->where('status', 'in_stock')->count()
                    : null,
            ],
            'movements' => $movements,
            'serials' => $serials,
            'movementTypes' => StockMovement::TYPES,
            'can' => [
                'update' => $request->user()->can('update', $product),
                'delete' => $request->user()->can('delete', $product),
                'adjust' => $request->user()->can('create', StockMovement::class),
            ],
        ]);
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        return Inertia::render('Inventory/Products/Edit', [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'description' => $product->description,
                'product_category_id' => $product->product_category_id,
                'brand' => $product->brand,
                'unit' => $product->unit,
                'cost_price' => Money::toDecimal($product->cost_price_pence),
                'sell_price' => Money::toDecimal($product->sell_price_pence),
                'reorder_level' => $product->reorder_level,
                'tracks_serial' => $product->tracks_serial,
                'is_active' => $product->is_active,
            ],
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        UpdateProduct::run($product, $request->validated());

        return redirect()
            ->route('inventory.products.show', $product)
            ->with('success', "{$product->name} was updated.");
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($product->stockOnHand() !== 0) {
            return back()->with('error', 'Stock must be zero before a product can be removed. Write it off first.');
        }

        $name = $product->name;
        $product->delete();

        return redirect()
            ->route('inventory.products.index')
            ->with('success', "{$name} was removed from the catalogue.");
    }

    /**
     * @return array<int, array{key: string, label: string, value: string|int, meta: string, icon: string}>
     */
    private function stats(): array
    {
        $activeProducts = Product::query()->where('is_active', true)->count();
        $totalProducts = Product::query()->count();
        $unitsInStock = (int) DB::table('stock_movements')->sum('quantity');
        $lowStock = Product::query()->lowStock()->count();

        $stockValue = (int) DB::query()
            ->fromSub(
                DB::table('stock_movements')
                    ->selectRaw('product_id, sum(quantity) as quantity')
                    ->groupBy('product_id'),
                'totals',
            )
            ->join('products', 'products.id', '=', 'totals.product_id')
            ->sum(DB::raw('totals.quantity * products.cost_price_pence'));

        return [
            [
                'key' => 'products',
                'label' => 'Active products',
                'value' => $activeProducts,
                'meta' => $totalProducts === $activeProducts
                    ? 'Whole catalogue active'
                    : ($totalProducts - $activeProducts).' archived product(s)',
                'icon' => 'box',
            ],
            [
                'key' => 'units',
                'label' => 'Units in stock',
                'value' => number_format($unitsInStock),
                'meta' => 'Derived from the movement ledger',
                'icon' => 'truck',
            ],
            [
                'key' => 'value',
                'label' => 'Stock at cost',
                'value' => Money::format($stockValue),
                'meta' => 'Cost price × quantity on hand',
                'icon' => 'banknotes',
            ],
            [
                'key' => 'low',
                'label' => 'Low stock lines',
                'value' => $lowStock,
                'meta' => $lowStock === 0 ? 'Everything above its reorder level' : 'At or below reorder level',
                'icon' => 'warning',
            ],
        ];
    }

    private function nextSku(): string
    {
        $latest = Product::withTrashed()->orderByDesc('id')->value('sku');

        if (! $latest || ! preg_match('/(\d+)$/', $latest, $matches)) {
            return 'TKS-1001';
        }

        return 'TKS-'.((int) $matches[1] + 1);
    }
}
