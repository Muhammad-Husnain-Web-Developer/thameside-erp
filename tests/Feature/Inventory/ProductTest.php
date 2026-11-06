<?php

use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use Inertia\Testing\AssertableInertia as Assert;

function productPayload(array $overrides = []): array
{
    return array_merge([
        'sku' => 'TKS-9001',
        'name' => 'Beaumont 90cm dual fuel range cooker',
        'description' => 'Stainless steel, two ovens, five burners.',
        'brand' => 'Beaumont',
        'unit' => 'each',
        'cost_price' => '1,180.00',
        'sell_price' => '1749.00',
        'reorder_level' => 3,
        'tracks_serial' => false,
        'is_active' => true,
        'opening_stock' => 0,
    ], $overrides);
}

it('lists the catalogue for warehouse staff', function () {
    $warehouse = User::factory()->create();
    $warehouse->assignRole('warehouse');

    ProductCategory::create(['name' => 'Range cookers']);

    $this->actingAs($warehouse)
        ->get('/inventory/products')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Products/Index')
            ->has('categories', 1)
            ->has('stats', 4)
            ->where('can.create', true)
        );
});

it('forbids sales staff from adding products', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales)
        ->post('/inventory/products', productPayload())
        ->assertForbidden();

    expect(Product::count())->toBe(0);
});

it('creates a product and parses money into pence', function () {
    $warehouse = User::factory()->create();
    $warehouse->assignRole('warehouse');

    $this->actingAs($warehouse)
        ->post('/inventory/products', productPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $product = Product::first();

    expect($product->cost_price_pence)->toBe(118000)
        ->and($product->sell_price_pence)->toBe(174900)
        ->and($product->tracks_serial)->toBeFalse();
});

it('records opening stock as the first ledger movement', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post('/inventory/products', productPayload(['opening_stock' => 4]))
        ->assertRedirect();

    $product = Product::first();

    expect($product->stockOnHand())->toBe(4)
        ->and($product->stockMovements()->value('reference'))->toBe('Opening stock');
});

it('requires a unique sku', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Product::create([
        'sku' => 'TKS-9001',
        'name' => 'Existing product',
        'unit' => 'each',
        'cost_price_pence' => 100,
        'sell_price_pence' => 200,
    ]);

    $this->actingAs($admin)
        ->post('/inventory/products', productPayload())
        ->assertSessionHasErrors('sku');

    expect(Product::count())->toBe(1);
});

it('shows the product with derived stock and serials', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $product = Product::create([
        'sku' => 'TKS-9100',
        'name' => 'American fridge freezer',
        'unit' => 'each',
        'cost_price_pence' => 92000,
        'sell_price_pence' => 138500,
        'tracks_serial' => true,
        'reorder_level' => 2,
    ]);

    \App\Modules\Inventory\Actions\RecordStockMovement::run($product, [
        'type' => 'receipt',
        'quantity' => 2,
        'serials' => ['ASH-AMER-0001', 'ASH-AMER-0002'],
    ]);

    $this->actingAs($admin)
        ->get("/inventory/products/{$product->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Products/Show')
            ->where('stock.on_hand', 2)
            ->where('stock.is_low', true)
            ->has('serials', 2)
            ->has('movements', 1)
            ->where('can.adjust', true)
        );
});

it('will not remove a product that still has stock', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $product = Product::create([
        'sku' => 'TKS-9200',
        'name' => 'Wine cooler',
        'unit' => 'each',
        'cost_price_pence' => 30000,
        'sell_price_pence' => 48000,
    ]);

    \App\Modules\Inventory\Actions\RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 1]);

    $this->actingAs($admin)
        ->delete("/inventory/products/{$product->id}")
        ->assertRedirect();

    expect($product->fresh())->not->toBeNull()
        ->and(Product::query()->whereKey($product->id)->exists())->toBeTrue();
});
