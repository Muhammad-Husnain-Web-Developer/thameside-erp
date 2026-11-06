<?php

use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Accounting\Support\Ledger;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Actions\RecordStockMovement;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Sales\Actions\CreateInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Actions\RecordCustomerReceipt;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    ChartOfAccounts::ensure();

    $this->customer = Customer::create([
        'code' => 'CUS-T1',
        'name' => 'Test kitchens',
        'payment_terms_days' => 30,
        'credit_limit_pence' => 500000,
        'is_active' => true,
    ]);

    $category = ProductCategory::firstOrCreate(['name' => 'Range cookers']);

    $this->product = Product::create([
        'sku' => 'TKS-9501',
        'name' => 'Test oven',
        'product_category_id' => $category->id,
        'unit' => 'each',
        'cost_price_pence' => 40000,
        'sell_price_pence' => 60000,
        'vat_rate_bps' => 2000,
        'reorder_level' => 1,
        'tracks_serial' => false,
        'is_active' => true,
    ]);

    RecordStockMovement::run($this->product, ['type' => 'receipt', 'quantity' => 10, 'unit_cost_pence' => 40000]);
});

function salesOrder(Customer $customer, Product $product, int $quantity = 2, string $price = '600.00'): SalesOrder
{
    return CreateSalesOrder::run([
        'customer_id' => $customer->id,
        'ordered_at' => '2026-11-01',
        'lines' => [
            ['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $price],
        ],
    ]);
}

it('totals a sales order with VAT', function () {
    $order = salesOrder($this->customer, $this->product, 2, '600.00');

    expect($order->number)->toBe('SO-000001')
        ->and($order->subtotal_pence)->toBe(120000)
        ->and($order->vat_pence)->toBe(24000)
        ->and($order->total_pence)->toBe(144000)
        ->and($order->status)->toBe(SalesOrder::STATUS_CONFIRMED);
});

it('invoices an order, releases stock and posts the sale', function () {
    $order = salesOrder($this->customer, $this->product, 2);

    $invoice = CreateInvoice::run($order);

    expect($invoice->number)->toBe('INV-000001')
        ->and($invoice->total_pence)->toBe(144000)
        ->and($invoice->journal_entry_id)->not->toBeNull()
        ->and($order->fresh()->status)->toBe(SalesOrder::STATUS_FULFILLED)
        ->and($this->product->stockOnHand())->toBe(8);

    $trial = Ledger::trialBalance();

    expect($trial['balanced'])->toBeTrue()
        ->and(Ledger::balancePence(ChartOfAccounts::TRADE_DEBTORS))->toBe(144000)
        ->and(Ledger::balancePence(ChartOfAccounts::SALES))->toBe(-120000)
        ->and(Ledger::balancePence(ChartOfAccounts::VAT_PAYABLE))->toBe(-24000);
});

it('links the invoice to the journal created by its event listener', function () {
    $invoice = CreateInvoice::run(salesOrder($this->customer, $this->product, 1));

    $journal = $invoice->journalEntry;

    expect($journal)->not->toBeNull()
        ->and($journal->source_type)->toBe(Invoice::class)
        ->and($journal->source_id)->toBe($invoice->id)
        ->and($journal->lines)->toHaveCount(3);
});

it('refuses to invoice an order twice', function () {
    $order = salesOrder($this->customer, $this->product, 1);

    CreateInvoice::run($order);

    expect(fn () => CreateInvoice::run($order->fresh()))->toThrow(ValidationException::class)
        ->and(Invoice::count())->toBe(1);
});

it('rolls the whole invoice back when stock is short', function () {
    $order = salesOrder($this->customer, $this->product, 20);

    expect(fn () => CreateInvoice::run($order))->toThrow(ValidationException::class);

    expect(Invoice::count())->toBe(0)
        ->and($order->fresh()->status)->toBe(SalesOrder::STATUS_CONFIRMED)
        ->and($this->product->stockOnHand())->toBe(10);
});

it('requires serial numbers when invoicing serial-tracked stock', function () {
    $serialProduct = Product::create([
        'sku' => 'TKS-9502',
        'name' => 'Serial oven',
        'product_category_id' => $this->product->product_category_id,
        'unit' => 'each',
        'cost_price_pence' => 70000,
        'sell_price_pence' => 99000,
        'vat_rate_bps' => 2000,
        'tracks_serial' => true,
        'is_active' => true,
    ]);

    RecordStockMovement::run($serialProduct, ['type' => 'receipt', 'quantity' => 2, 'serials' => ['OV-1', 'OV-2']]);

    $order = CreateSalesOrder::run([
        'customer_id' => $this->customer->id,
        'lines' => [['product_id' => $serialProduct->id, 'quantity' => 1, 'unit_price' => '990.00']],
    ]);

    expect(fn () => CreateInvoice::run($order))->toThrow(ValidationException::class);

    $invoice = CreateInvoice::run($order, ['serials' => [$order->lines->first()->id => ['OV-2']]]);

    expect($invoice->total_pence)->toBe(118800)
        ->and($serialProduct->stockOnHand())->toBe(1)
        ->and($serialProduct->serials()->where('serial_number', 'OV-2')->value('status'))->toBe('sold');
});

it('records a customer receipt and clears the debtor', function () {
    $invoice = CreateInvoice::run(salesOrder($this->customer, $this->product, 1));
    $outstanding = $invoice->outstandingPence();

    RecordCustomerReceipt::run($invoice, ['amount' => number_format($outstanding / 100, 2, '.', '')]);

    expect($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID)
        ->and($invoice->fresh()->amount_paid_pence)->toBe($invoice->total_pence)
        ->and(Ledger::balancePence(ChartOfAccounts::TRADE_DEBTORS))->toBe(0)
        ->and(Ledger::balancePence(ChartOfAccounts::BANK))->toBe($invoice->total_pence);
});

it('lets sales staff invoice an order through the endpoint', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $order = salesOrder($this->customer, $this->product, 1);

    $this->actingAs($sales)
        ->post("/sales/orders/{$order->id}/invoice")
        ->assertRedirect();

    expect(Invoice::count())->toBe(1);
});

it('forbids warehouse staff from invoicing', function () {
    $warehouse = User::factory()->create();
    $warehouse->assignRole('warehouse');

    $order = salesOrder($this->customer, $this->product, 1);

    $this->actingAs($warehouse)
        ->post("/sales/orders/{$order->id}/invoice")
        ->assertForbidden();

    expect(Invoice::count())->toBe(0);
});
