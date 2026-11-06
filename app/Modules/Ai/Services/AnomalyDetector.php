<?php

namespace App\Modules\Ai\Services;

use App\Modules\Core\Support\Money;
use App\Modules\Sales\Models\Invoice;
use Illuminate\Support\Facades\DB;

/**
 * Rule-based anomaly detection over the operational ledger. Flags stock
 * write-offs that are large against the norm, invoices well above the average
 * and debts left overdue for more than a month.
 */
class AnomalyDetector
{
    private const WINDOW_DAYS = 90;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function detect(int $limit = 8): array
    {
        $since = now()->subDays(self::WINDOW_DAYS);
        $anomalies = [];

        $adjustments = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->whereIn('stock_movements.type', ['adjustment', 'write_off'])
            ->where('stock_movements.occurred_at', '>=', $since->toDateString())
            ->select('stock_movements.id', 'stock_movements.quantity', 'stock_movements.unit_cost_pence', 'stock_movements.occurred_at', 'stock_movements.note', 'products.sku', 'products.name')
            ->orderByDesc('stock_movements.occurred_at')
            ->limit(50)
            ->get();

        $averageLoss = (int) round($adjustments->avg(fn ($row) => abs((int) $row->quantity) * (int) $row->unit_cost_pence) ?? 0);

        foreach ($adjustments as $row) {
            $loss = abs((int) $row->quantity) * (int) $row->unit_cost_pence;

            if ($averageLoss > 0 && $loss <= $averageLoss * 2.5) {
                continue;
            }

            $anomalies[] = [
                'type' => 'stock',
                'severity' => $loss >= $averageLoss * 4 ? 'high' : 'medium',
                'title' => "Large stock movement on {$row->name}",
                'detail' => abs((int) $row->quantity).' units ('.Money::format($loss).') written off or adjusted on '.substr((string) $row->occurred_at, 0, 10).'.',
                'href' => '/inventory/products/'.$row->id,
            ];
        }

        $invoices = Invoice::query()
            ->where('invoice_date', '>=', $since->toDateString())
            ->orderByDesc('total_pence')
            ->limit(50)
            ->get();

        $averageInvoice = (int) round($invoices->avg('total_pence') ?? 0);

        foreach ($invoices as $invoice) {
            if ($averageInvoice > 0 && $invoice->total_pence <= $averageInvoice * 2.5) {
                continue;
            }

            $anomalies[] = [
                'type' => 'sales',
                'severity' => 'medium',
                'title' => "Unusually large invoice {$invoice->number}",
                'detail' => Money::format($invoice->total_pence).' against an average of '.Money::format($averageInvoice).' over the last '.self::WINDOW_DAYS.' days.',
                'href' => '/sales/invoices/'.$invoice->id,
            ];
        }

        $overdue = Invoice::query()
            ->whereIn('status', ['posted', 'part_paid'])
            ->where('due_date', '<', now()->subDays(30)->toDateString())
            ->where('total_pence', '>', DB::raw('amount_paid_pence'))
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        foreach ($overdue as $invoice) {
            $anomalies[] = [
                'type' => 'credit',
                'severity' => 'high',
                'title' => "{$invoice->number} is more than 30 days overdue",
                'detail' => $invoice->displayOutstanding().' outstanding since '.$invoice->due_date?->format('j M Y').'.',
                'href' => '/sales/invoices/'.$invoice->id,
            ];
        }

        return array_slice($anomalies, 0, $limit);
    }
}
