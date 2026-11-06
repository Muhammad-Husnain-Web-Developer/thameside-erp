<?php

namespace App\Modules\Sales\Models;

use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLine extends Model
{
    protected $fillable = [
        'sales_order_id',
        'product_id',
        'description',
        'quantity',
        'quantity_fulfilled',
        'unit_price_pence',
        'vat_rate_bps',
        'line_total_pence',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_fulfilled' => 'integer',
            'unit_price_pence' => 'integer',
            'vat_rate_bps' => 'integer',
            'line_total_pence' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outstandingQuantity(): int
    {
        return max(0, $this->quantity - $this->quantity_fulfilled);
    }

    public function displayUnitPrice(): string
    {
        return Money::format($this->unit_price_pence);
    }
}
