<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NominalAccount extends Model
{
    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    public const TYPES = [
        self::TYPE_ASSET => 'Assets',
        self::TYPE_LIABILITY => 'Liabilities',
        self::TYPE_EQUITY => 'Equity',
        self::TYPE_INCOME => 'Income',
        self::TYPE_EXPENSE => 'Expenses',
    ];

    protected $fillable = ['code', 'name', 'type', 'description', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Debit-positive balance: assets and expenses grow with debits, while
     * liabilities, equity and income grow with credits.
     */
    public function normalBalance(): string
    {
        return in_array($this->type, [self::TYPE_ASSET, self::TYPE_EXPENSE], true) ? 'debit' : 'credit';
    }
}
