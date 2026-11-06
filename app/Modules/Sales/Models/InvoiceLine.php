<?php

namespace App\Modules\Sales\Models;

use App\Modules\Core\Support\Money;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price_pence',
        'vat_rate_bps',
        'line_total_pence',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_pence' => 'integer',
            'vat_rate_bps' => 'integer',
            'line_total_pence' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function displayUnitPrice(): string
    {
        return Money::format($this->unit_price_pence);
    }
}
