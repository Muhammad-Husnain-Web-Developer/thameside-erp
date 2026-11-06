<?php

namespace App\Modules\Sales\Models;

use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Core\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use LogsActivity;
    use SoftDeletes;

    public const STATUS_POSTED = 'posted';
    public const STATUS_PART_PAID = 'part_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'number',
        'customer_id',
        'sales_order_id',
        'journal_entry_id',
        'invoice_date',
        'due_date',
        'subtotal_pence',
        'vat_pence',
        'total_pence',
        'amount_paid_pence',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal_pence' => 'integer',
            'vat_pence' => 'integer',
            'total_pence' => 'integer',
            'amount_paid_pence' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'status', 'total_pence', 'amount_paid_pence'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('invoice');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function outstandingPence(): int
    {
        return max(0, $this->total_pence - $this->amount_paid_pence);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function displayOutstanding(): string
    {
        return Money::format($this->outstandingPence());
    }
}
