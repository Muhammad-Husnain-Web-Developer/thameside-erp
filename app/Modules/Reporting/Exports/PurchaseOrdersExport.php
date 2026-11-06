<?php

namespace App\Modules\Reporting\Exports;

use App\Modules\Core\Support\Money;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseOrdersExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    /**
     * @return Collection<int, array<int, mixed>>
     */
    public function collection(): Collection
    {
        return PurchaseOrder::query()
            ->with('supplier')
            ->orderBy('ordered_at')
            ->get()
            ->map(fn (PurchaseOrder $order) => [
                $order->number,
                $order->ordered_at?->format('Y-m-d'),
                $order->expected_at?->format('Y-m-d'),
                $order->supplier?->name,
                Money::toDecimal($order->subtotal_pence),
                Money::toDecimal($order->vat_pence),
                Money::toDecimal($order->total_pence),
                $order->status,
            ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Order', 'Ordered', 'Expected', 'Supplier', 'Net', 'VAT', 'Total', 'Status'];
    }
}
