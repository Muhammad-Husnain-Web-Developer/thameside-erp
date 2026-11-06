<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Sales\Actions\RecordCustomerReceipt;
use App\Modules\Sales\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $status = (string) $request->string('status');
        $search = trim((string) $request->string('q'));

        $invoices = Invoice::query()
            ->with('customer')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
            }))
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'customer' => $invoice->customer?->name,
                'invoice_date' => $invoice->invoice_date?->format('j M Y'),
                'due_date' => $invoice->due_date?->format('j M Y'),
                'total' => Money::format($invoice->total_pence),
                'outstanding' => $invoice->displayOutstanding(),
                'status' => $invoice->status,
                'is_overdue' => ! $invoice->isPaid() && $invoice->due_date?->isPast(),
            ]);

        $unpaid = Invoice::query()->whereIn('status', ['posted', 'part_paid']);

        return Inertia::render('Sales/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => ['status' => $status, 'q' => $search],
            'stats' => [
                [
                    'key' => 'outstanding',
                    'label' => 'Outstanding',
                    'value' => Money::format((int) (clone $unpaid)->sum('total_pence') - (int) (clone $unpaid)->sum('amount_paid_pence')),
                    'meta' => (clone $unpaid)->count().' open invoices',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'overdue',
                    'label' => 'Overdue',
                    'value' => Invoice::query()->whereIn('status', ['posted', 'part_paid'])->where('due_date', '<', now())->count(),
                    'meta' => 'Chase these first',
                    'icon' => 'warning',
                ],
                [
                    'key' => 'month',
                    'label' => 'Invoiced this month',
                    'value' => Money::format((int) Invoice::query()->where('invoice_date', '>=', now()->startOfMonth())->sum('total_pence')),
                    'meta' => now()->format('F Y'),
                    'icon' => 'receipt',
                ],
                [
                    'key' => 'paid',
                    'label' => 'Paid invoices',
                    'value' => Invoice::query()->where('status', 'paid')->count(),
                    'meta' => 'Settled in full',
                    'icon' => 'check',
                ],
            ],
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'lines', 'journalEntry', 'order']);

        return Inertia::render('Sales/Invoices/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'invoice_date' => $invoice->invoice_date?->format('j F Y'),
                'due_date' => $invoice->due_date?->format('j F Y'),
                'subtotal' => Money::format($invoice->subtotal_pence),
                'vat' => Money::format($invoice->vat_pence),
                'total' => Money::format($invoice->total_pence),
                'paid' => Money::format($invoice->amount_paid_pence),
                'outstanding' => $invoice->displayOutstanding(),
                'outstanding_pence' => $invoice->outstandingPence(),
                'customer' => [
                    'name' => $invoice->customer?->name,
                    'code' => $invoice->customer?->code,
                    'email' => $invoice->customer?->email,
                    'address' => $invoice->customer?->address,
                    'city' => $invoice->customer?->city,
                    'postcode' => $invoice->customer?->postcode,
                ],
                'order' => $invoice->order ? ['id' => $invoice->order->id, 'number' => $invoice->order->number] : null,
                'journal' => $invoice->journalEntry ? ['id' => $invoice->journalEntry->id, 'number' => $invoice->journalEntry->number] : null,
            ],
            'lines' => $invoice->lines->map(fn ($line) => [
                'id' => $line->id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => Money::format($line->unit_price_pence),
                'total' => Money::format($line->line_total_pence),
            ]),
            'can' => [
                'pay' => $request->user()->can('pay', $invoice) && $invoice->outstandingPence() > 0,
            ],
        ]);
    }

    public function pdf(Invoice $invoice): HttpResponse
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'lines', 'order']);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => config('erp.company'),
        ])->download("invoice-{$invoice->number}.pdf");
    }

    public function pay(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('pay', $invoice);

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'received_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        RecordCustomerReceipt::run($invoice, $request->validated(), $request->user());

        return redirect()
            ->route('sales.invoices.show', $invoice)
            ->with('success', 'Receipt posted to the ledger.');
    }
}
