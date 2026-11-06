<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use App\Modules\Purchasing\Models\SupplierBill;
use App\Modules\Reporting\Exports\InvoicesExport;
use App\Modules\Reporting\Exports\PurchaseOrdersExport;
use App\Modules\Reporting\Exports\StockValuationExport;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Actions\RecordCustomerReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function salesSummary(Request $request): Response
    {
        $request->user()->can('reports.view') || abort(403);

        $months = collect(range(5, 0))->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));

        $series = $months->map(function (Carbon $month) {
            $start = $month->copy()->startOfMonth()->toDateString();
            $end = $month->copy()->endOfMonth()->toDateString();

            $sales = (int) Invoice::query()->whereBetween('invoice_date', [$start, $end])->sum('subtotal_pence');
            $purchases = (int) SupplierBill::query()->whereBetween('bill_date', [$start, $end])->sum('subtotal_pence');

            $cogs = (int) DB::table('stock_movements')
                ->where('type', 'sale')
                ->whereBetween('occurred_at', [$start.' 00:00:00', $end.' 23:59:59'])
                ->selectRaw('coalesce(sum(-quantity * unit_cost_pence), 0) as cogs')
                ->value('cogs');

            return [
                'label' => $month->format('M Y'),
                'sales' => Money::toFloat($sales),
                'purchases' => Money::toFloat($purchases),
                'cogs' => Money::toFloat($cogs),
                'profit' => Money::toFloat($sales - $cogs),
            ];
        });

        $topProducts = DB::table('invoice_lines')
            ->join('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id')
            ->leftJoin('products', 'products.id', '=', 'invoice_lines.product_id')
            ->whereNull('invoices.deleted_at')
            ->groupBy('invoice_lines.product_id', 'products.sku', 'products.name')
            ->selectRaw('invoice_lines.product_id, products.sku, products.name, sum(invoice_lines.quantity) as units, sum(invoice_lines.line_total_pence) as revenue')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'sku' => $row->sku,
                'name' => $row->name ?? 'Custom line',
                'units' => (int) $row->units,
                'revenue' => Money::format((int) $row->revenue),
            ]);

        $monthSales = (int) Invoice::query()->where('invoice_date', '>=', now()->startOfMonth())->sum('subtotal_pence');
        $monthCogs = (int) DB::table('stock_movements')
            ->where('type', 'sale')
            ->whereBetween('occurred_at', [now()->startOfMonth()->toDateString().' 00:00:00', now()->endOfMonth()->toDateString().' 23:59:59'])
            ->selectRaw('coalesce(sum(-quantity * unit_cost_pence), 0) as cogs')
            ->value('cogs');

        return Inertia::render('Reports/SalesSummary', [
            'series' => $series,
            'topProducts' => $topProducts,
            'stats' => [
                [
                    'key' => 'sales',
                    'label' => 'Sales this month',
                    'value' => Money::format($monthSales),
                    'meta' => 'Net of VAT',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'profit',
                    'label' => 'Gross profit this month',
                    'value' => Money::format($monthSales - $monthCogs),
                    'meta' => $monthSales > 0 ? round(($monthSales - $monthCogs) / $monthSales * 100, 1).'% margin' : 'No sales yet',
                    'icon' => 'chart',
                ],
                [
                    'key' => 'orders',
                    'label' => 'Awaiting invoice',
                    'value' => \App\Modules\Sales\Models\SalesOrder::query()->where('status', 'confirmed')->count(),
                    'meta' => 'Confirmed sales orders',
                    'icon' => 'truck',
                ],
                [
                    'key' => 'receivable',
                    'label' => 'Debtor balance',
                    'value' => Money::format((int) Invoice::query()->whereIn('status', ['posted', 'part_paid'])->sum('total_pence') - (int) Invoice::query()->whereIn('status', ['posted', 'part_paid'])->sum('amount_paid_pence')),
                    'meta' => 'Owed by customers',
                    'icon' => 'building',
                ],
            ],
        ]);
    }

    public function stockValuation(Request $request): Response
    {
        $request->user()->can('reports.view') || abort(403);

        $products = Product::query()
            ->with('category')
            ->withStock()
            ->get()
            ->map(fn (Product $product) => [
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category?->name ?? 'Uncategorised',
                'on_hand' => $product->stockOnHand(),
                'unit' => $product->unit,
                'cost' => $product->cost_price_pence,
                'value' => $product->stockValuePence(),
                'retail' => $product->stockOnHand() * $product->sell_price_pence,
                'is_low' => $product->isLowStock(),
            ]);

        $byCategory = $products
            ->groupBy('category')
            ->map(fn ($rows, $category) => [
                'category' => $category,
                'units' => $rows->sum('on_hand'),
                'value' => $rows->sum('value'),
                'retail' => $rows->sum('retail'),
            ])
            ->sortByDesc('value')
            ->values();

        return Inertia::render('Reports/StockValuation', [
            'categories' => $byCategory->map(fn (array $row) => [
                'category' => $row['category'],
                'units' => (int) $row['units'],
                'value' => Money::format((int) $row['value']),
                'retail' => Money::format((int) $row['retail']),
                'value_float' => Money::toFloat((int) $row['value']),
            ]),
            'products' => $products->sortByDesc('value')->take(40)->values()->map(fn (array $row) => [
                'sku' => $row['sku'],
                'name' => $row['name'],
                'category' => $row['category'],
                'on_hand' => $row['on_hand'],
                'unit' => $row['unit'],
                'value' => Money::format($row['value']),
                'retail' => Money::format($row['retail']),
                'is_low' => $row['is_low'],
            ]),
            'stats' => [
                [
                    'key' => 'value',
                    'label' => 'Stock at cost',
                    'value' => Money::format((int) $products->sum('value')),
                    'meta' => $products->sum('on_hand').' units on hand',
                    'icon' => 'box',
                ],
                [
                    'key' => 'retail',
                    'label' => 'Stock at retail',
                    'value' => Money::format((int) $products->sum('retail')),
                    'meta' => 'Potential sales value',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'margin',
                    'label' => 'Potential margin',
                    'value' => Money::format((int) $products->sum('retail') - (int) $products->sum('value')),
                    'meta' => 'Retail less cost',
                    'icon' => 'chart',
                ],
                [
                    'key' => 'low',
                    'label' => 'Low stock lines',
                    'value' => $products->where('is_low', true)->count(),
                    'meta' => 'At or below reorder level',
                    'icon' => 'warning',
                ],
            ],
        ]);
    }

    public function exportInvoices(Request $request): BinaryFileResponse
    {
        $request->user()->can('reports.export') || abort(403);

        $from = $request->filled('from') ? Carbon::parse($request->string('from'))->toDateString() : null;
        $to = $request->filled('to') ? Carbon::parse($request->string('to'))->toDateString() : null;

        return Excel::download(new InvoicesExport($from, $to), 'sales-invoices-'.now()->format('Y-m-d').'.xlsx');
    }

    public function exportStock(Request $request): BinaryFileResponse
    {
        $request->user()->can('reports.export') || abort(403);

        return Excel::download(new StockValuationExport, 'stock-valuation-'.now()->format('Y-m-d').'.xlsx');
    }

    public function exportPurchaseOrders(Request $request): BinaryFileResponse
    {
        $request->user()->can('reports.export') || abort(403);

        return Excel::download(new PurchaseOrdersExport, 'purchase-orders-'.now()->format('Y-m-d').'.xlsx');
    }
}
