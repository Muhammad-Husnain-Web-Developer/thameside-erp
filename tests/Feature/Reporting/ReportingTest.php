<?php

use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Actions\RecordStockMovement;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Sales\Actions\CreateInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Models\Customer;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    ChartOfAccounts::ensure();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $customer = Customer::create([
        'code' => 'CUS-R1',
        'name' => 'Report test customer',
        'payment_terms_days' => 30,
        'is_active' => true,
    ]);

    $category = ProductCategory::firstOrCreate(['name' => 'Report category']);

    $product = Product::create([
        'sku' => 'TKS-9701',
        'name' => 'Report test oven',
        'product_category_id' => $category->id,
        'unit' => 'each',
        'cost_price_pence' => 30000,
        'sell_price_pence' => 50000,
        'vat_rate_bps' => 2000,
        'reorder_level' => 1,
        'tracks_serial' => false,
        'is_active' => true,
    ]);

    RecordStockMovement::run($product, ['type' => 'receipt', 'quantity' => 5, 'unit_cost_pence' => 30000]);

    $order = CreateSalesOrder::run([
        'customer_id' => $customer->id,
        'lines' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '500.00']],
    ]);

    $this->invoice = CreateInvoice::run($order);
});

it('renders the sales summary with six months of chart data', function () {
    $this->actingAs($this->admin)
        ->get('/reports/sales')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/SalesSummary')
            ->has('series', 6)
            ->where('series.5.sales', 500)
            ->has('topProducts', 1)
            ->has('stats', 4)
        );
});

it('renders the stock valuation report', function () {
    $this->actingAs($this->admin)
        ->get('/reports/stock')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports/StockValuation')
            ->has('categories')
            ->has('products')
            ->has('stats', 4)
        );
});

it('downloads an invoice as a PDF', function () {
    $response = $this->actingAs($this->admin)
        ->get("/sales/invoices/{$this->invoice->id}/pdf");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');

    expect(str_starts_with($response->getContent(), '%PDF'))->toBeTrue();
});

it('exports sales invoices to Excel', function () {
    $response = $this->actingAs($this->admin)->get('/reports/exports/invoices');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    expect(DB::table('invoices')->count())->toBe(1);
});

it('exports stock valuation to Excel', function () {
    $response = $this->actingAs($this->admin)->get('/reports/exports/stock');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('forbids sales staff from exporting spreadsheets', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales)
        ->get('/reports/exports/invoices')
        ->assertForbidden();
});
