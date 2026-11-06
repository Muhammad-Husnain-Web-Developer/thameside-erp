<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'nominal_account_id',
        'description',
        'debit_pence',
        'credit_pence',
    ];

    protected function casts(): array
    {
        return [
            'debit_pence' => 'integer',
            'credit_pence' => 'integer',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(NominalAccount::class, 'nominal_account_id');
    }
}
