<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Sales\Http\Requests\StoreCustomerRequest;
use App\Modules\Sales\Http\Requests\UpdateCustomerRequest;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Customer::class);

        $search = trim((string) $request->string('q'));

        $customers = Customer::query()
            ->withCount('orders')
            ->withSum(['invoices as invoiced_total' => fn ($query) => $query->whereIn('status', ['posted', 'part_paid'])], 'total_pence')
            ->withSum(['invoices as paid_total' => fn ($query) => $query->whereIn('status', ['posted', 'part_paid'])], 'amount_paid_pence')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Customer $customer) => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'contact_name' => $customer->contact_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'orders' => $customer->orders_count,
                'outstanding' => Money::format((int) $customer->invoiced_total - (int) $customer->paid_total),
                'credit_limit' => Money::format($customer->credit_limit_pence),
                'is_active' => $customer->is_active,
                'can' => ['update' => $request->user()->can('update', $customer)],
            ]);

        $unpaid = Invoice::query()->whereIn('status', ['posted', 'part_paid']);

        return Inertia::render('Sales/Customers/Index', [
            'customers' => $customers,
            'filters' => ['q' => $search],
            'stats' => [
                [
                    'key' => 'customers',
                    'label' => 'Active customers',
                    'value' => Customer::query()->where('is_active', true)->count(),
                    'meta' => Customer::query()->count().' on file',
                    'icon' => 'users',
                ],
                [
                    'key' => 'receivable',
                    'label' => 'Outstanding receivable',
                    'value' => Money::format((int) (clone $unpaid)->sum('total_pence') - (int) (clone $unpaid)->sum('amount_paid_pence')),
                    'meta' => 'Awaiting customer payment',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'overdue',
                    'label' => 'Overdue invoices',
                    'value' => Invoice::query()->whereIn('status', ['posted', 'part_paid'])->where('due_date', '<', now())->count(),
                    'meta' => 'Past the due date',
                    'icon' => 'warning',
                ],
                [
                    'key' => 'orders',
                    'label' => 'Open orders',
                    'value' => \App\Modules\Sales\Models\SalesOrder::query()->where('status', 'confirmed')->count(),
                    'meta' => 'Awaiting invoice',
                    'icon' => 'truck',
                ],
            ],
            'can' => ['create' => $request->user()->can('create', Customer::class)],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Customer::class);

        return Inertia::render('Sales/Customers/Create', [
            'suggestedCode' => $this->nextCode(),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = $data['code'] ?? $this->nextCode();
        $data['credit_limit_pence'] = Money::parse($data['credit_limit'] ?? 0);
        $data['is_active'] = $data['is_active'] ?? true;
        unset($data['credit_limit']);

        $customer = Customer::create($data);

        return redirect()
            ->route('sales.customers.index')
            ->with('success', "{$customer->name} was added to the customer list.");
    }

    public function edit(Customer $customer): Response
    {
        $this->authorize('update', $customer);

        return Inertia::render('Sales/Customers/Edit', [
            'customer' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'contact_name' => $customer->contact_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'city' => $customer->city,
                'postcode' => $customer->postcode,
                'credit_limit' => Money::toDecimal($customer->credit_limit_pence),
                'payment_terms_days' => $customer->payment_terms_days,
                'notes' => $customer->notes,
                'is_active' => $customer->is_active,
            ],
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $data['credit_limit_pence'] = Money::parse($data['credit_limit'] ?? 0);
        unset($data['credit_limit']);

        $customer->update($data);

        return redirect()
            ->route('sales.customers.index')
            ->with('success', "{$customer->name} was updated.");
    }

    private function nextCode(): string
    {
        $latest = Customer::withTrashed()->orderByDesc('id')->value('code');
        $next = $latest && preg_match('/(\d+)$/', $latest, $matches) ? ((int) $matches[1]) + 1 : 1;

        return 'CUS-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
