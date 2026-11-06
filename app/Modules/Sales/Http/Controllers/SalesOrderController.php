<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use App\Modules\Sales\Actions\CreateInvoice;
use App\Modules\Sales\Actions\CreateSalesOrder;
use App\Modules\Sales\Http\Requests\StoreSalesOrderRequest;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Models\SalesOrderLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SalesOrder::class);

        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status');

        $orders = SalesOrder::query()
            ->with('customer')
            ->withCount('lines')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
            }))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('ordered_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SalesOrder $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'customer' => $order->customer?->name,
                'ordered_at' => $order->ordered_at?->format('j M Y'),
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'lines' => $order->lines_count,
                'total' => Money::format($order->total_pence),
            ]);

        return Inertia::render('Sales/Orders/Index', [
            'orders' => $orders,
            'filters' => ['q' => $search, 'status' => $status],
            'statuses' => SalesOrder::STATUSES,
            'stats' => [
                [
                    'key' => 'open',
                    'label' => 'Awaiting invoice',
                    'value' => SalesOrder::query()->where('status', 'confirmed')->count(),
                    'meta' => 'Confirmed orders to fulfil',
                    'icon' => 'truck',
                ],
                [
                    'key' => 'value',
                    'label' => 'Order book',
                    'value' => Money::format((int) SalesOrder::query()->where('status', 'confirmed')->sum('total_pence')),
                    'meta' => 'Value incl. VAT',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'month',
                    'label' => 'Invoiced this month',
                    'value' => SalesOrder::query()->where('status', 'fulfilled')->where('fulfilled_at', '>=', now()->startOfMonth())->count(),
                    'meta' => now()->format('F Y'),
                    'icon' => 'check',
                ],
                [
                    'key' => 'customers',
                    'label' => 'Customers ordering',
                    'value' => SalesOrder::query()->distinct()->count('customer_id'),
                    'meta' => 'With at least one order',
                    'icon' => 'users',
                ],
            ],
            'can' => ['create' => $request->user()->can('create', SalesOrder::class)],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SalesOrder::class);

        return Inertia::render('Sales/Orders/Create', [
            'customers' => Customer::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Customer $customer) => [
                    'value' => $customer->id,
                    'label' => "{$customer->name} ({$customer->code})",
                ]),
            'products' => Product::query()
                ->where('is_active', true)
                ->withStock()
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'value' => $product->id,
                    'label' => "{$product->sku} · {$product->name}",
                    'price' => Money::toDecimal($product->sell_price_pence),
                    'stock' => $product->stockOnHand(),
                    'tracks_serial' => $product->tracks_serial,
                ]),
            'today' => now()->toDateString(),
            'suggestedNumber' => $this->nextNumber(),
        ]);
    }

    public function store(StoreSalesOrderRequest $request): RedirectResponse
    {
        $order = CreateSalesOrder::run($request->validated(), $request->user());

        return redirect()
            ->route('sales.orders.show', $order)
            ->with('success', "{$order->number} was raised for {$order->customer->name}.");
    }

    public function show(Request $request, SalesOrder $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['lines.product', 'customer', 'author', 'invoices']);

        return Inertia::render('Sales/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'ordered_at' => $order->ordered_at?->format('j F Y'),
                'fulfilled_at' => $order->fulfilled_at?->format('j F Y'),
                'notes' => $order->notes,
                'author' => $order->author?->name,
                'subtotal' => Money::format($order->subtotal_pence),
                'vat' => Money::format($order->vat_pence),
                'total' => Money::format($order->total_pence),
                'customer' => [
                    'id' => $order->customer->id,
                    'name' => $order->customer->name,
                    'code' => $order->customer->code,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->phone,
                    'terms' => $order->customer->payment_terms_days,
                ],
            ],
            'lines' => $order->lines->map(fn (SalesOrderLine $line) => [
                'id' => $line->id,
                'sku' => $line->product?->sku,
                'product' => $line->product?->name,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'quantity_fulfilled' => $line->quantity_fulfilled,
                'unit_price' => Money::format($line->unit_price_pence),
                'total' => Money::format($line->line_total_pence),
                'tracks_serial' => (bool) $line->product?->tracks_serial,
                'stock' => $line->product?->stockOnHand() ?? 0,
            ]),
            'invoices' => $order->invoices->map(fn ($invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'total' => Money::format($invoice->total_pence),
                'status' => $invoice->status,
            ]),
            'can' => [
                'invoice' => $request->user()->can('invoice', $order) && $order->canInvoice(),
            ],
        ]);
    }

    public function invoice(Request $request, SalesOrder $order): RedirectResponse
    {
        $this->authorize('invoice', $order);

        $request->validate([
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'serials' => ['nullable', 'array'],
            'serials.*' => ['array'],
            'serials.*.*' => ['string', 'max:100'],
        ]);

        $invoice = CreateInvoice::run(
            $order,
            [
                'invoice_date' => $request->input('invoice_date'),
                'due_date' => $request->input('due_date'),
                'serials' => $request->input('serials', []),
            ],
            $request->user(),
        );

        return redirect()
            ->route('sales.invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->number} posted and stock released.");
    }

    private function nextNumber(): string
    {
        $latest = SalesOrder::withTrashed()->orderByDesc('id')->value('number');
        $next = $latest && preg_match('/(\d+)$/', $latest, $matches) ? ((int) $matches[1]) + 1 : 1;

        return 'SO-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
