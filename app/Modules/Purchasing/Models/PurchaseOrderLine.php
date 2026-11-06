<?php

namespace App\Modules\Purchasing\Models;

use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'description',
        'quantity',
        'quantity_received',
        'unit_cost_pence',
        'vat_rate_bps',
        'line_total_pence',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_received' => 'integer',
            'unit_cost_pence' => 'integer',
            'vat_rate_bps' => 'integer',
            'line_total_pence' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outstandingQuantity(): int
    {
        return max(0, $this->quantity - $this->quantity_received);
    }

    public function displayUnitCost(): string
    {
        return Money::format($this->unit_cost_pence);
    }
}
