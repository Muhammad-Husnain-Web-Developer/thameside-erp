<?php

use App\Modules\Core\Models\User;
use App\Modules\Inventory\Actions\RecordStockMovement;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Inventory\Models\ProductSerial;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Validation\ValidationException;

function makeProduct(array $overrides = []): Product
{
    $category = ProductCategory::firstOrCreate(['name' => 'Range cookers']);

    return Product::create(array_merge([
        'sku' => 'TKS-'.fake()->unique()->numberBetween(2000, 9999),
        'name' => 'Test range cooker',
        'product_category_id' => $category->id,
        'unit' => 'each',
        'cost_price_pence' => 50000,
        'sell_price_pence' => 80000,
        'reorder_level' => 2,
        'tracks_serial' => false,
        'is_active' => true,
    ], $overrides));
}

it('derives stock on hand from the movement ledger', function () {
    $product = makeProduct();

    RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 5]);

    expect($product->stockOnHand())->toBe(5)
        ->and(StockMovement::count())->toBe(1)
        ->and($product->fresh()->stockOnHand())->toBe(5);
});

it('never stores a quantity column on products', function () {
    $product = makeProduct();

    RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 3]);

    expect($product->getAttributes())->not->toHaveKey('quantity')
        ->and($product->stockOnHand())->toBe(3);
});

it('reduces stock on a sale', function () {
    $product = makeProduct();

    RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 10]);
    $sale = RecordStockMovement::run($product, ['type' => 'sale', 'quantity' => 4]);

    expect($sale->quantity)->toBe(-4)
        ->and($product->stockOnHand())->toBe(6);
});

it('refuses to take stock below zero', function () {
    $product = makeProduct();

    RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 2]);

    expect(fn () => RecordStockMovement::run($product, ['type' => 'sale', 'quantity' => 3]))
        ->toThrow(ValidationException::class);

    expect($product->stockOnHand())->toBe(2);
});

it('rejects a zero quantity', function () {
    $product = makeProduct();

    expect(fn () => RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 0]))
        ->toThrow(ValidationException::class);
});

it('allows signed adjustments with a reason', function () {
    $product = makeProduct();

    RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 6]);
    RecordStockMovement::run($product, ['type' => 'adjustment', 'quantity' => -2, 'note' => 'Damaged in transit']);

    expect($product->stockOnHand())->toBe(4)
        ->and(StockMovement::query()->where('type', 'adjustment')->first()->note)->toBe('Damaged in transit');
});

it('requires a serial number for every serial-tracked unit received', function () {
    $product = makeProduct(['tracks_serial' => true]);

    expect(fn () => RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 2]))
        ->toThrow(ValidationException::class);

    RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 2, 'serials' => ['SN-A1', 'SN-A2']]);

    expect($product->serials()->count())->toBe(2)
        ->and($product->serials()->where('status', 'in_stock')->count())->toBe(2);
});

it('rejects duplicate serial numbers', function () {
    $product = makeProduct(['tracks_serial' => true]);

    RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 1, 'serials' => ['SN-DUP']]);

    expect(fn () => RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 1, 'serials' => ['SN-DUP']]))
        ->toThrow(ValidationException::class);

    expect($product->stockOnHand())->toBe(1);
});

it('only sells serial numbers that are on the shelf', function () {
    $product = makeProduct(['tracks_serial' => true]);

    RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 2, 'serials' => ['SN-B1', 'SN-B2']]);

    expect(fn () => RecordStockMovement::run($product, ['type' => 'sale', 'quantity' => 1, 'serials' => ['SN-UNKNOWN']]))
        ->toThrow(ValidationException::class);

    $sale = RecordStockMovement::run($product, ['type' => 'sale', 'quantity' => 1, 'serials' => ['SN-B2']]);

    expect($sale->quantity)->toBe(-1)
        ->and($product->stockOnHand())->toBe(1)
        ->and(ProductSerial::where('serial_number', 'SN-B2')->value('status'))->toBe('sold')
        ->and(ProductSerial::where('serial_number', 'SN-B1')->value('status'))->toBe('in_stock');
});

it('refuses adjustments on serial-tracked products', function () {
    $product = makeProduct(['tracks_serial' => true]);

    RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 1, 'serials' => ['SN-C1']]);

    expect(fn () => RecordStockMovement::run($product, ['type' => 'adjustment', 'quantity' => -1]))
        ->toThrow(ValidationException::class);
});

it('records a movement through the ledger endpoint', function () {
    $warehouse = User::factory()->create();
    $warehouse->assignRole('warehouse');
    $product = makeProduct();

    $this->actingAs($warehouse)
        ->post('/inventory/stock', [
            'product_id' => $product->id,
            'type' => 'receipt',
            'quantity' => 12,
            'reference' => 'GRN-1001',
        ])
        ->assertRedirect('/inventory/stock')
        ->assertSessionHas('success');

    expect($product->stockOnHand())->toBe(12)
        ->and(StockMovement::query()->first()->user_id)->toBe($warehouse->id);
});

it('forbids sales staff from recording stock movements', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');
    $product = makeProduct();

    $this->actingAs($sales)
        ->post('/inventory/stock', [
            'product_id' => $product->id,
            'type' => 'receipt',
            'quantity' => 4,
        ])
        ->assertForbidden();

    expect($product->stockOnHand())->toBe(0);
});
