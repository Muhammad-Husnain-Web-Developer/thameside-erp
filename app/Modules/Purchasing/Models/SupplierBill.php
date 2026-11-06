<?php

namespace App\Modules\Purchasing\Models;

use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Core\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierBill extends Model
{
    use SoftDeletes;

    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PART_PAID = 'part_paid';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'number',
        'supplier_id',
        'purchase_order_id',
        'journal_entry_id',
        'bill_date',
        'due_date',
        'reference',
        'subtotal_pence',
        'vat_pence',
        'total_pence',
        'amount_paid_pence',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'subtotal_pence' => 'integer',
            'vat_pence' => 'integer',
            'total_pence' => 'integer',
            'amount_paid_pence' => 'integer',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
