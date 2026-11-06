<?php

namespace App\Modules\Purchasing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Purchasing\Http\Requests\StoreSupplierRequest;
use App\Modules\Purchasing\Http\Requests\UpdateSupplierRequest;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierBill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Supplier::class);

        $search = trim((string) $request->string('q'));

        $suppliers = Supplier::query()
            ->withCount('purchaseOrders')
            ->withSum(['bills as billed_total' => fn ($query) => $query->whereIn('status', ['unpaid', 'part_paid'])], 'total_pence')
            ->withSum(['bills as paid_total' => fn ($query) => $query->whereIn('status', ['unpaid', 'part_paid'])], 'amount_paid_pence')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'code' => $supplier->code,
                'name' => $supplier->name,
                'contact_name' => $supplier->contact_name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'payment_terms_days' => $supplier->payment_terms_days,
                'is_active' => $supplier->is_active,
                'orders' => $supplier->purchase_orders_count,
                'outstanding' => Money::format((int) $supplier->billed_total - (int) $supplier->paid_total),
                'can' => ['update' => $request->user()->can('update', $supplier)],
            ]);

        $outstanding = SupplierBill::query()
            ->whereIn('status', [SupplierBill::STATUS_UNPAID, SupplierBill::STATUS_PART_PAID])
            ->selectRaw('sum(total_pence) as total, sum(amount_paid_pence) as paid')
            ->first();

        return Inertia::render('Purchasing/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => ['q' => $search],
            'stats' => [
                [
                    'key' => 'suppliers',
                    'label' => 'Active suppliers',
                    'value' => Supplier::query()->where('is_active', true)->count(),
                    'meta' => Supplier::query()->count().' on file',
                    'icon' => 'building',
                ],
                [
                    'key' => 'orders',
                    'label' => 'Open purchase orders',
                    'value' => PurchaseOrder::query()->whereIn('status', ['draft', 'ordered'])->count(),
                    'meta' => 'Awaiting goods receipt',
                    'icon' => 'truck',
                ],
                [
                    'key' => 'payable',
                    'label' => 'Outstanding payables',
                    'value' => Money::format((int) ($outstanding->total ?? 0) - (int) ($outstanding->paid ?? 0)),
                    'meta' => 'Unpaid supplier bills',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'terms',
                    'label' => 'Average terms',
                    'value' => (int) round((float) Supplier::query()->avg('payment_terms_days')).' days',
                    'meta' => 'Agreed payment window',
                    'icon' => 'chart',
                ],
            ],
            'can' => ['create' => $request->user()->can('create', Supplier::class)],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Supplier::class);

        return Inertia::render('Purchasing/Suppliers/Create', [
            'suggestedCode' => $this->nextCode(),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = $data['code'] ?? $this->nextCode();
        $data['is_active'] = $data['is_active'] ?? true;

        $supplier = Supplier::create($data);

        return redirect()
            ->route('purchasing.suppliers.index')
            ->with('success', "{$supplier->name} was added to the supplier list.");
    }

    public function edit(Supplier $supplier): Response
    {
        $this->authorize('update', $supplier);

        return Inertia::render('Purchasing/Suppliers/Edit', [
            'supplier' => [
                'id' => $supplier->id,
                'code' => $supplier->code,
                'name' => $supplier->name,
                'contact_name' => $supplier->contact_name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'address' => $supplier->address,
                'city' => $supplier->city,
                'postcode' => $supplier->postcode,
                'vat_number' => $supplier->vat_number,
                'payment_terms_days' => $supplier->payment_terms_days,
                'notes' => $supplier->notes,
                'is_active' => $supplier->is_active,
            ],
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('purchasing.suppliers.index')
            ->with('success', "{$supplier->name} was updated.");
    }

    private function nextCode(): string
    {
        $latest = Supplier::withTrashed()->orderByDesc('id')->value('code');
        $next = $latest && preg_match('/(\d+)$/', $latest, $matches) ? ((int) $matches[1]) + 1 : 1;

        return 'SUP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
