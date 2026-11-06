<?php

namespace App\Modules\Hr\Models;

use App\Modules\Core\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'gross_pence',
        'paye_pence',
        'ni_pence',
        'pension_pence',
        'net_pence',
    ];

    protected function casts(): array
    {
        return [
            'gross_pence' => 'integer',
            'paye_pence' => 'integer',
            'ni_pence' => 'integer',
            'pension_pence' => 'integer',
            'net_pence' => 'integer',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function deductionsPence(): int
    {
        return $this->paye_pence + $this->ni_pence + $this->pension_pence;
    }

    public function displayNet(): string
    {
        return Money::format($this->net_pence);
    }
}
