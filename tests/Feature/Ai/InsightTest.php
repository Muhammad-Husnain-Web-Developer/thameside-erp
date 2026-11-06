<?php

use App\Modules\Ai\Jobs\RefreshAiInsight;
use App\Modules\Ai\Services\AnomalyDetector;
use App\Modules\Ai\Services\DemandForecaster;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Actions\RecordStockMovement;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Cache::flush();

    $category = ProductCategory::firstOrCreate(['name' => 'AI category']);

    $this->product = Product::create([
        'sku' => 'TKS-9801',
        'name' => 'Fast moving oven',
        'product_category_id' => $category->id,
        'unit' => 'each',
        'cost_price_pence' => 10000,
        'sell_price_pence' => 18000,
        'vat_rate_bps' => 2000,
        'reorder_level' => 3,
        'tracks_serial' => false,
        'is_active' => true,
    ]);

    RecordStockMovement::run($this->product, [
        'type' => 'receipt',
        'quantity' => 12,
        'occurred_at' => now()->subMonths(5)->toDateString(),
    ]);

    foreach ([1, 2, 3, 4, 5] as $month) {
        RecordStockMovement::run($this->product, [
            'type' => 'sale',
            'quantity' => 2,
            'occurred_at' => now()->subMonths($month)->toDateString(),
        ]);
    }
});

it('forecasts a reorder when cover drops below three months', function () {
    $suggestions = app(DemandForecaster::class)->suggestions();

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0]['sku'])->toBe('TKS-9801')
        ->and($suggestions[0]['suggested_order'])->toBeGreaterThan(0)
        ->and($suggestions[0]['urgent'])->toBeTrue();
});

it('flags a stock movement that is far larger than the norm', function () {
    RecordStockMovement::run($this->product, [
        'type' => 'receipt',
        'quantity' => 50,
        'occurred_at' => now()->subDays(30)->toDateString(),
    ]);

    RecordStockMovement::run($this->product, [
        'type' => 'write_off',
        'quantity' => 1,
        'note' => 'Damaged in transit',
        'occurred_at' => now()->subDays(20)->toDateString(),
    ]);

    RecordStockMovement::run($this->product, [
        'type' => 'write_off',
        'quantity' => 2,
        'note' => 'Damaged in transit',
        'occurred_at' => now()->subDays(18)->toDateString(),
    ]);

    RecordStockMovement::run($this->product, [
        'type' => 'write_off',
        'quantity' => 20,
        'note' => 'Pallet dropped at the depot',
        'occurred_at' => now()->subDays(2)->toDateString(),
    ]);

    $anomalies = app(AnomalyDetector::class)->detect();

    expect($anomalies)->not->toBeEmpty()
        ->and(collect($anomalies)->pluck('title')->implode(' '))->toContain('Fast moving oven');
});

it('renders the insights page with a model summary', function () {
    config(['erp.ai.key' => 'test-key', 'erp.ai.model' => 'test-model']);

    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => 'Reorder the fast moving oven now. Review the write-offs before month end.']]],
        ]),
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/ai')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Ai/Insights/Index')
            ->where('insight.source', 'model')
            ->where('insight.model', 'test-model')
            ->has('forecast', 1)
            ->has('stats', 4)
        );

    Http::assertSentCount(1);
});

it('queues a retry and falls back when the provider rate limits', function () {
    Queue::fake();
    config(['erp.ai.key' => 'test-key']);

    Http::fake(['*' => Http::response(['error' => 'rate limited'], 429)]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/ai')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('insight.source', 'queued'));

    Queue::assertPushed(RefreshAiInsight::class);
});

it('works without an API key using the deterministic fallback', function () {
    config(['erp.ai.key' => null]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/ai')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('insight.source', 'fallback')
            ->where('insight.model', null)
        );
});

it('forbids users without the insights permission', function () {
    $noAccess = User::factory()->create();

    $this->actingAs($noAccess)
        ->get('/ai')
        ->assertForbidden();
});
