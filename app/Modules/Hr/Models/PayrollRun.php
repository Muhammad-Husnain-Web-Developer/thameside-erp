<?php

namespace App\Modules\Hr\Models;

use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Core\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'reference',
        'period_start',
        'period_end',
        'pay_date',
        'status',
        'employee_count',
        'gross_pence',
        'paye_pence',
        'ni_pence',
        'pension_pence',
        'net_pence',
        'journal_entry_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'pay_date' => 'date',
            'employee_count' => 'integer',
            'gross_pence' => 'integer',
            'paye_pence' => 'integer',
            'ni_pence' => 'integer',
            'pension_pence' => 'integer',
            'net_pence' => 'integer',
        ];
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function periodLabel(): string
    {
        return $this->period_start?->format('F Y') ?? '';
    }

    public function displayGross(): string
    {
        return Money::format($this->gross_pence);
    }
}
