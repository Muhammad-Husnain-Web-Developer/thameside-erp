<?php

namespace App\Modules\Purchasing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use App\Modules\Purchasing\Actions\CreatePurchaseOrder;
use App\Modules\Purchasing\Actions\ReceivePurchaseOrder;
use App\Modules\Purchasing\Http\Requests\StorePurchaseOrderRequest;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseOrderLine;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status');

        $orders = PurchaseOrder::query()
            ->with('supplier')
            ->withCount('lines')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$search}%"));
            }))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('ordered_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PurchaseOrder $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'supplier' => $order->supplier?->name,
                'ordered_at' => $order->ordered_at?->format('j M Y'),
                'expected_at' => $order->expected_at?->format('j M Y'),
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'lines' => $order->lines_count,
                'total' => Money::format($order->total_pence),
            ]);

        $awaiting = PurchaseOrder::query()
            ->whereIn('status', [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_ORDERED])
            ->sum('total_pence');

        return Inertia::render('Purchasing/PurchaseOrders/Index', [
            'orders' => $orders,
            'filters' => ['q' => $search, 'status' => $status],
            'statuses' => PurchaseOrder::STATUSES,
            'stats' => [
                [
                    'key' => 'open',
                    'label' => 'Orders awaiting receipt',
                    'value' => PurchaseOrder::query()->whereIn('status', ['draft', 'ordered'])->count(),
                    'meta' => 'Goods not yet booked in',
                    'icon' => 'truck',
                ],
                [
                    'key' => 'value',
                    'label' => 'On order',
                    'value' => Money::format((int) $awaiting),
                    'meta' => 'Committed spend, incl. VAT',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'month',
                    'label' => 'Received this month',
                    'value' => PurchaseOrder::query()->where('status', 'received')->where('received_at', '>=', now()->startOfMonth())->count(),
                    'meta' => now()->format('F Y'),
                    'icon' => 'box',
                ],
                [
                    'key' => 'suppliers',
                    'label' => 'Suppliers ordering',
                    'value' => PurchaseOrder::query()->distinct()->count('supplier_id'),
                    'meta' => 'With at least one order',
                    'icon' => 'building',
                ],
            ],
            'can' => ['create' => $request->user()->can('create', PurchaseOrder::class)],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PurchaseOrder::class);

        return Inertia::render('Purchasing/PurchaseOrders/Create', [
            'suppliers' => Supplier::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Supplier $supplier) => [
                    'value' => $supplier->id,
                    'label' => "{$supplier->name} ({$supplier->code})",
                    'terms' => $supplier->payment_terms_days,
                ]),
            'products' => Product::query()
                ->where('is_active', true)
                ->withStock()
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'value' => $product->id,
                    'label' => "{$product->sku} · {$product->name}",
                    'cost' => Money::toDecimal($product->cost_price_pence),
                    'cost_display' => Money::format($product->cost_price_pence),
                    'stock' => $product->stockOnHand(),
                    'tracks_serial' => $product->tracks_serial,
                ]),
            'today' => now()->toDateString(),
            'suggestedNumber' => $this->nextNumber(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $order = CreatePurchaseOrder::run($request->validated(), $request->user());

        return redirect()
            ->route('purchasing.purchase-orders.show', $order)
            ->with('success', "{$order->number} was raised with {$order->supplier->name}.");
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['lines.product', 'supplier', 'author', 'bills']);

        return Inertia::render('Purchasing/PurchaseOrders/Show', [
            'order' => [
                'id' => $purchaseOrder->id,
                'number' => $purchaseOrder->number,
                'status' => $purchaseOrder->status,
                'status_label' => $purchaseOrder->statusLabel(),
                'ordered_at' => $purchaseOrder->ordered_at?->format('j F Y'),
                'expected_at' => $purchaseOrder->expected_at?->format('j F Y'),
                'received_at' => $purchaseOrder->received_at?->format('j F Y'),
                'notes' => $purchaseOrder->notes,
                'author' => $purchaseOrder->author?->name,
                'subtotal' => Money::format($purchaseOrder->subtotal_pence),
                'vat' => Money::format($purchaseOrder->vat_pence),
                'total' => Money::format($purchaseOrder->total_pence),
                'supplier' => [
                    'id' => $purchaseOrder->supplier->id,
                    'name' => $purchaseOrder->supplier->name,
                    'code' => $purchaseOrder->supplier->code,
                    'email' => $purchaseOrder->supplier->email,
                    'phone' => $purchaseOrder->supplier->phone,
                    'terms' => $purchaseOrder->supplier->payment_terms_days,
                ],
            ],
            'lines' => $purchaseOrder->lines->map(fn (PurchaseOrderLine $line) => [
                'id' => $line->id,
                'product_id' => $line->product_id,
                'sku' => $line->product?->sku,
                'product' => $line->product?->name,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'quantity_received' => $line->quantity_received,
                'outstanding' => $line->outstandingQuantity(),
                'unit_cost' => Money::format($line->unit_cost_pence),
                'total' => Money::format($line->line_total_pence),
                'tracks_serial' => (bool) $line->product?->tracks_serial,
            ]),
            'bills' => $purchaseOrder->bills->map(fn ($bill) => [
                'id' => $bill->id,
                'number' => $bill->number,
                'due_date' => $bill->due_date?->format('j M Y'),
                'total' => Money::format($bill->total_pence),
                'status' => $bill->status,
            ]),
            'can' => [
                'receive' => $request->user()->can('receive', $purchaseOrder) && $purchaseOrder->canReceive(),
            ],
        ]);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('receive', $purchaseOrder);

        $request->validate([
            'received_at' => ['nullable', 'date'],
            'serials' => ['nullable', 'array'],
            'serials.*' => ['array'],
            'serials.*.*' => ['string', 'max:100'],
        ]);

        $bill = ReceivePurchaseOrder::run(
            $purchaseOrder,
            [
                'received_at' => $request->input('received_at'),
                'serials' => $request->input('serials', []),
            ],
            $request->user(),
        );

        return redirect()
            ->route('purchasing.purchase-orders.show', $purchaseOrder)
            ->with('success', "Goods received and bill {$bill->number} posted to the ledger.");
    }

    private function nextNumber(): string
    {
        $latest = PurchaseOrder::withTrashed()->orderByDesc('id')->value('number');
        $next = $latest && preg_match('/(\d+)$/', $latest, $matches) ? ((int) $matches[1]) + 1 : 1;

        return 'PO-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
