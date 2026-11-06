<?php

namespace App\Modules\Sales\Models;

use App\Modules\Core\Models\User;
use App\Modules\Core\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesOrder extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_FULFILLED => 'Fulfilled',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'number',
        'customer_id',
        'ordered_at',
        'fulfilled_at',
        'status',
        'subtotal_pence',
        'vat_pence',
        'total_pence',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'date',
            'fulfilled_at' => 'date',
            'subtotal_pence' => 'integer',
            'vat_pence' => 'integer',
            'total_pence' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'status', 'subtotal_pence', 'vat_pence', 'total_pence'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('sales_order');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function canInvoice(): bool
    {
        return $this->status === self::STATUS_CONFIRMED && $this->lines->isNotEmpty();
    }

    public function displayTotal(): string
    {
        return Money::format($this->total_pence);
    }
}
