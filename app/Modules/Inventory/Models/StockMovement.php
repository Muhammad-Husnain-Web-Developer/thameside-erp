<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Core\Models\User;
use App\Modules\Core\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockMovement extends Model
{
    use LogsActivity;

    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_SALE = 'sale';
    public const TYPE_RETURN = 'return';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_WRITE_OFF = 'write_off';

    /** Types that add stock to the shelf. */
    public const INBOUND = [self::TYPE_RECEIPT, self::TYPE_RETURN];

    /** Types that remove stock from the shelf. */
    public const OUTBOUND = [self::TYPE_SALE, self::TYPE_WRITE_OFF];

    public const TYPES = [
        self::TYPE_RECEIPT => 'Goods receipt',
        self::TYPE_SALE => 'Sale',
        self::TYPE_RETURN => 'Customer return',
        self::TYPE_ADJUSTMENT => 'Adjustment',
        self::TYPE_WRITE_OFF => 'Write-off',
    ];

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'unit_cost_pence',
        'reference',
        'note',
        'source_type',
        'source_id',
        'user_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost_pence' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'type', 'quantity', 'reference'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('stock');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function isInbound(): bool
    {
        return $this->quantity > 0;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function signedQuantity(): string
    {
        return ($this->quantity > 0 ? '+' : '').$this->quantity;
    }

    public function valuePence(): int
    {
        return abs($this->quantity) * (int) ($this->unit_cost_pence ?? $this->product?->cost_price_pence ?? 0);
    }

    public function displayValue(): string
    {
        return Money::format($this->valuePence());
    }
}
