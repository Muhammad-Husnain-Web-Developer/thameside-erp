<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Core\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'product_category_id',
        'brand',
        'unit',
        'cost_price_pence',
        'sell_price_pence',
        'vat_rate_bps',
        'tracks_serial',
        'reorder_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price_pence' => 'integer',
            'sell_price_pence' => 'integer',
            'vat_rate_bps' => 'integer',
            'tracks_serial' => 'boolean',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['sku', 'name', 'cost_price_pence', 'sell_price_pence', 'tracks_serial', 'reorder_level', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('product');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    /**
     * Stock on hand is always derived from the movement ledger.
     */
    public function stockOnHand(): int
    {
        return (int) ($this->attributes['stock_on_hand'] ?? $this->stockMovements()->sum('quantity'));
    }

    public function isLowStock(): bool
    {
        return $this->stockOnHand() <= $this->reorder_level;
    }

    public function stockValuePence(): int
    {
        return $this->stockOnHand() * $this->cost_price_pence;
    }

    public function marginPence(): int
    {
        return $this->sell_price_pence - $this->cost_price_pence;
    }

    public function marginPercent(): float
    {
        if ($this->sell_price_pence <= 0) {
            return 0.0;
        }

        return round($this->marginPence() / $this->sell_price_pence * 100, 1);
    }

    public function displayPrice(): string
    {
        return Money::format($this->sell_price_pence);
    }

    public function scopeWithStock(Builder $query): Builder
    {
        return $query->withSum('stockMovements as stock_on_hand', 'quantity');
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw(
            '(select coalesce(sum(quantity), 0) from stock_movements where stock_movements.product_id = products.id) <= products.reorder_level'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
