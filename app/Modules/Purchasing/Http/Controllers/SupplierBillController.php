<?php

namespace App\Modules\Purchasing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Purchasing\Actions\RecordSupplierPayment;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierBill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierBillController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SupplierBill::class);

        $status = (string) $request->string('status');
        $supplier = $request->integer('supplier') ?: null;

        $bills = SupplierBill::query()
            ->with('supplier')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($supplier, fn ($query) => $query->where('supplier_id', $supplier))
            ->latest('bill_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SupplierBill $bill) => [
                'id' => $bill->id,
                'number' => $bill->number,
                'supplier' => $bill->supplier?->name,
                'bill_date' => $bill->bill_date?->format('j M Y'),
                'due_date' => $bill->due_date?->format('j M Y'),
                'total' => Money::format($bill->total_pence),
                'outstanding' => $bill->displayOutstanding(),
                'status' => $bill->status,
                'is_overdue' => ! $bill->isPaid() && $bill->due_date?->isPast(),
            ]);

        $unpaid = SupplierBill::query()->whereIn('status', ['unpaid', 'part_paid']);

        return Inertia::render('Purchasing/Bills/Index', [
            'bills' => $bills,
            'filters' => ['status' => $status, 'supplier' => $supplier],
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'stats' => [
                [
                    'key' => 'outstanding',
                    'label' => 'Outstanding',
                    'value' => Money::format((int) (clone $unpaid)->sum('total_pence') - (int) (clone $unpaid)->sum('amount_paid_pence')),
                    'meta' => (clone $unpaid)->count().' open bills',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'overdue',
                    'label' => 'Overdue',
                    'value' => SupplierBill::query()->whereIn('status', ['unpaid', 'part_paid'])->where('due_date', '<', now())->count(),
                    'meta' => 'Past the due date',
                    'icon' => 'warning',
                ],
                [
                    'key' => 'month',
                    'label' => 'Billed this month',
                    'value' => Money::format((int) SupplierBill::query()->where('bill_date', '>=', now()->startOfMonth())->sum('total_pence')),
                    'meta' => now()->format('F Y'),
                    'icon' => 'receipt',
                ],
                [
                    'key' => 'paid',
                    'label' => 'Paid bills',
                    'value' => SupplierBill::query()->where('status', 'paid')->count(),
                    'meta' => 'Settled in full',
                    'icon' => 'check',
                ],
            ],
        ]);
    }

    public function show(Request $request, SupplierBill $bill): Response
    {
        $this->authorize('view', $bill);

        $bill->load(['supplier', 'purchaseOrder', 'journalEntry.lines.account']);

        return Inertia::render('Purchasing/Bills/Show', [
            'bill' => [
                'id' => $bill->id,
                'number' => $bill->number,
                'status' => $bill->status,
                'bill_date' => $bill->bill_date?->format('j F Y'),
                'due_date' => $bill->due_date?->format('j F Y'),
                'reference' => $bill->reference,
                'subtotal' => Money::format($bill->subtotal_pence),
                'vat' => Money::format($bill->vat_pence),
                'total' => Money::format($bill->total_pence),
                'paid' => Money::format($bill->amount_paid_pence),
                'outstanding' => $bill->displayOutstanding(),
                'outstanding_pence' => $bill->outstandingPence(),
                'supplier' => [
                    'name' => $bill->supplier?->name,
                    'code' => $bill->supplier?->code,
                    'email' => $bill->supplier?->email,
                ],
                'order' => $bill->purchaseOrder ? [
                    'id' => $bill->purchaseOrder->id,
                    'number' => $bill->purchaseOrder->number,
                ] : null,
                'journal' => $bill->journalEntry ? [
                    'id' => $bill->journalEntry->id,
                    'number' => $bill->journalEntry->number,
                ] : null,
            ],
            'can' => [
                'pay' => $request->user()->can('pay', $bill) && $bill->outstandingPence() > 0,
            ],
        ]);
    }

    public function pay(Request $request, SupplierBill $bill): RedirectResponse
    {
        $this->authorize('pay', $bill);

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        RecordSupplierPayment::run($bill, $request->validated(), $request->user());

        return redirect()
            ->route('purchasing.bills.show', $bill)
            ->with('success', 'Payment posted to the ledger.');
    }
}
