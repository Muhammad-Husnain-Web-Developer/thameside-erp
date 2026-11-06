<?php

namespace App\Modules\Accounting\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JournalEntry extends Model
{
    use LogsActivity;

    protected $fillable = [
        'number',
        'date',
        'description',
        'source_type',
        'source_id',
        'posted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'description', 'date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('journal');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function totalDebitPence(): int
    {
        return (int) $this->lines->sum('debit_pence');
    }

    public function totalCreditPence(): int
    {
        return (int) $this->lines->sum('credit_pence');
    }

    public function isBalanced(): bool
    {
        return $this->totalDebitPence() === $this->totalCreditPence()
            && $this->totalDebitPence() > 0;
    }

    public function sourceLabel(): string
    {
        return $this->source_type ? class_basename($this->source_type) : 'Manual journal';
    }
}
