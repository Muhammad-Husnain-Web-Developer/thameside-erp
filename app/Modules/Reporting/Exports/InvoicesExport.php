<?php

namespace App\Modules\Reporting\Exports;

use App\Modules\Core\Support\Money;
use App\Modules\Sales\Models\Invoice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoicesExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private readonly ?string $from = null, private readonly ?string $to = null)
    {
    }

    /**
     * @return Collection<int, array<int, mixed>>
     */
    public function collection(): Collection
    {
        return Invoice::query()
            ->with('customer')
            ->when($this->from, fn ($query) => $query->where('invoice_date', '>=', $this->from))
            ->when($this->to, fn ($query) => $query->where('invoice_date', '<=', $this->to))
            ->orderBy('invoice_date')
            ->get()
            ->map(fn (Invoice $invoice) => [
                $invoice->number,
                $invoice->invoice_date?->format('Y-m-d'),
                $invoice->due_date?->format('Y-m-d'),
                $invoice->customer?->name,
                $invoice->customer?->code,
                Money::toDecimal($invoice->subtotal_pence),
                Money::toDecimal($invoice->vat_pence),
                Money::toDecimal($invoice->total_pence),
                Money::toDecimal($invoice->amount_paid_pence),
                Money::toDecimal($invoice->outstandingPence()),
                $invoice->status,
            ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Invoice',
            'Invoice date',
            'Due date',
            'Customer',
            'Account code',
            'Net',
            'VAT',
            'Total',
            'Paid',
            'Outstanding',
            'Status',
        ];
    }
}
