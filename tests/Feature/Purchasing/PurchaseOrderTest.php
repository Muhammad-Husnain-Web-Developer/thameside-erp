<?php

use App\Modules\Accounting\Actions\PostJournalEntry;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Accounting\Support\Ledger;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Purchasing\Actions\CreatePurchaseOrder;
use App\Modules\Purchasing\Actions\ReceivePurchaseOrder;
use App\Modules\Purchasing\Actions\RecordSupplierPayment;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierBill;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    ChartOfAccounts::ensure();

    $this->supplier = Supplier::create([
        'code' => 'SUP-T1',
        'name' => 'Test supplier',
        'payment_terms_days' => 30,
        'is_active' => true,
    ]);

    $category = ProductCategory::firstOrCreate(['name' => 'Range cookers']);

    $this->product = Product::create([
        'sku' => 'TKS-9001',
        'name' => 'Test cooker',
        'product_category_id' => $category->id,
        'unit' => 'each',
        'cost_price_pence' => 50000,
        'sell_price_pence' => 80000,
        'vat_rate_bps' => 2000,
        'reorder_level' => 1,
        'tracks_serial' => false,
        'is_active' => true,
    ]);
});

function raiseOrder(Supplier $supplier, Product $product, int $quantity = 4, string $cost = '500.00'): PurchaseOrder
{
    return CreatePurchaseOrder::run([
        'supplier_id' => $supplier->id,
        'ordered_at' => '2026-11-01',
        'lines' => [
            ['product_id' => $product->id, 'quantity' => $quantity, 'unit_cost' => $cost],
        ],
    ]);
}

it('totals a purchase order with VAT', function () {
    $order = raiseOrder($this->supplier, $this->product, 4, '500.00');

    expect($order->number)->toBe('PO-000001')
        ->and($order->subtotal_pence)->toBe(200000)
        ->and($order->vat_pence)->toBe(40000)
        ->and($order->total_pence)->toBe(240000)
        ->and($order->status)->toBe(PurchaseOrder::STATUS_ORDERED);
});

it('receives goods into stock and posts a balanced supplier bill', function () {
    $order = raiseOrder($this->supplier, $this->product, 4);

    $bill = ReceivePurchaseOrder::run($order);

    expect($this->product->stockOnHand())->toBe(4)
        ->and($order->fresh()->status)->toBe(PurchaseOrder::STATUS_RECEIVED)
        ->and($bill->number)->toBe('BILL-000001')
        ->and($bill->total_pence)->toBe(240000)
        ->and($bill->journal_entry_id)->not->toBeNull();

    $trial = Ledger::trialBalance();

    expect($trial['balanced'])->toBeTrue()
        ->and(Ledger::balancePence(ChartOfAccounts::INVENTORY))->toBe(200000)
        ->and(Ledger::balancePence(ChartOfAccounts::VAT_RECEIVABLE))->toBe(40000)
        ->and(Ledger::balancePence(ChartOfAccounts::TRADE_CREDITORS))->toBe(-240000);

    $this->assertDatabaseHas('journal_lines', ['debit_pence' => 40000]);

    $vat = Ledger::vatSummary(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'));

    expect($vat['purchases_net'])->toBe(200000);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/accounting/reports/vat-return?from=2026-11-01&to=2026-11-30')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.purchases_net_pence', 200000)
            ->where('boxes.6.box', '7')
            ->where('boxes.6.value', '£2,000.00')
        );
});

it('refuses to receive the same order twice', function () {
    $order = raiseOrder($this->supplier, $this->product, 2);

    ReceivePurchaseOrder::run($order);

    expect(fn () => ReceivePurchaseOrder::run($order->fresh()))
        ->toThrow(ValidationException::class);

    expect($this->product->stockOnHand())->toBe(2)
        ->and(JournalEntry::count())->toBe(1);
});

it('records a supplier payment against the bill and clears it', function () {
    $order = raiseOrder($this->supplier, $this->product, 1);
    $bill = ReceivePurchaseOrder::run($order);

    RecordSupplierPayment::run($bill, ['amount' => '100.00']);

    expect($bill->fresh()->amount_paid_pence)->toBe(10000)
        ->and($bill->fresh()->status)->toBe(SupplierBill::STATUS_PART_PAID)
        ->and(Ledger::balancePence(ChartOfAccounts::TRADE_CREDITORS))->toBe(-50000);

    RecordSupplierPayment::run($bill->fresh(), ['amount' => '500.00']);

    expect($bill->fresh()->status)->toBe(SupplierBill::STATUS_PAID)
        ->and(Ledger::balancePence(ChartOfAccounts::TRADE_CREDITORS))->toBe(0)
        ->and(Ledger::balancePence(ChartOfAccounts::BANK))->toBe(-60000);
});

it('refuses a payment larger than the outstanding balance', function () {
    $order = raiseOrder($this->supplier, $this->product, 1);
    $bill = ReceivePurchaseOrder::run($order);

    expect(fn () => RecordSupplierPayment::run($bill, ['amount' => '999.00']))
        ->toThrow(ValidationException::class);
});

it('lets warehouse staff receive a purchase order', function () {
    $warehouse = User::factory()->create();
    $warehouse->assignRole('warehouse');

    $order = raiseOrder($this->supplier, $this->product, 3);

    $this->actingAs($warehouse)
        ->post("/purchasing/purchase-orders/{$order->id}/receive")
        ->assertRedirect("/purchasing/purchase-orders/{$order->id}");

    expect($this->product->stockOnHand())->toBe(3)
        ->and(SupplierBill::count())->toBe(1);
});

it('forbids sales staff from raising a purchase order', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales)
        ->post('/purchasing/purchase-orders', [
            'supplier_id' => $this->supplier->id,
            'ordered_at' => '2026-11-01',
            'lines' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_cost' => '500.00'],
            ],
        ])
        ->assertForbidden();

    expect(PurchaseOrder::count())->toBe(0);
});

it('posts a manual opening journal before receiving so the trial balance stays whole', function () {
    PostJournalEntry::run(
        ['description' => 'Opening capital'],
        [
            ['account' => ChartOfAccounts::BANK, 'debit' => '5000.00'],
            ['account' => ChartOfAccounts::SHARE_CAPITAL, 'credit' => '5000.00'],
        ],
    );

    $order = raiseOrder($this->supplier, $this->product, 2);
    ReceivePurchaseOrder::run($order);

    $trial = Ledger::trialBalance();

    expect($trial['balanced'])->toBeTrue()
        ->and($trial['total_debit'])->toBeGreaterThan(0);
});
