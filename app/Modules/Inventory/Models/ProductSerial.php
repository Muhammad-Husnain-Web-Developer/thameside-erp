<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSerial extends Model
{
    public const STATUS_IN_STOCK = 'in_stock';
    public const STATUS_SOLD = 'sold';
    public const STATUS_WRITTEN_OFF = 'written_off';

    protected $fillable = [
        'product_id',
        'serial_number',
        'status',
        'stock_movement_id',
        'received_at',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'sold_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function isInStock(): bool
    {
        return $this->status === self::STATUS_IN_STOCK;
    }
}
