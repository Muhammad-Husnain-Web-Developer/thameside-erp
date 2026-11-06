<?php

namespace App\Modules\Hr\Actions;

use App\Modules\Accounting\Actions\PostJournalEntry;
use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\PayrollRun;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Monthly payroll. Payslips are calculated per employee, then the whole run
 * posts one balanced journal: wages expense against PAYE/NI, pension and net
 * pay. Running the same period twice is refused by the database and here.
 */
class RunPayroll extends Action
{
    /** Monthly personal allowance used for the demo PAYE/NI estimate. */
    private const MONTHLY_ALLOWANCE_PENCE = 104_800;

    private const PAYE_BPS = 2000;
    private const NI_BPS = 800;
    private const PENSION_BPS = 500;

    /**
     * @param  array{month?: ?string, pay_date?: ?string}  $data
     */
    public function handle(array $data, ?User $user = null): PayrollRun
    {
        // Make sure the payroll control accounts exist even on an older database.
        ChartOfAccounts::ensure();

        $month = isset($data['month']) ? Carbon::parse($data['month'].'-01') : now()->startOfMonth();
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();
        $payDate = isset($data['pay_date']) ? Carbon::parse($data['pay_date']) : $periodEnd->copy();

        if (PayrollRun::query()->where('period_start', $periodStart->toDateString())->exists()) {
            throw ValidationException::withMessages([
                'month' => 'Payroll has already been run for '.$periodStart->format('F Y').'.',
            ]);
        }

        $employees = Employee::query()
            ->where('is_active', true)
            ->where('annual_salary_pence', '>', 0)
            ->where('started_on', '<=', $periodEnd->toDateString())
            ->where(fn ($query) => $query->whereNull('ended_on')->orWhere('ended_on', '>=', $periodStart->toDateString()))
            ->orderBy('last_name')
            ->get();

        if ($employees->isEmpty()) {
            throw ValidationException::withMessages([
                'month' => 'There are no active employees to pay for this period.',
            ]);
        }

        $run = PayrollRun::create([
            'reference' => 'PR-'.$periodStart->format('Ym'),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'pay_date' => $payDate->toDateString(),
            'status' => 'posted',
            'employee_count' => $employees->count(),
            'gross_pence' => 0,
            'paye_pence' => 0,
            'ni_pence' => 0,
            'pension_pence' => 0,
            'net_pence' => 0,
            'created_by' => $user?->id ?? auth()->id(),
        ]);

        $totals = ['gross' => 0, 'paye' => 0, 'ni' => 0, 'pension' => 0, 'net' => 0];

        foreach ($employees as $employee) {
            $gross = max(0, $employee->monthlyGrossPence());
            $taxable = max(0, $gross - self::MONTHLY_ALLOWANCE_PENCE);
            $paye = (int) round($taxable * self::PAYE_BPS / 10000);
            $ni = (int) round($taxable * self::NI_BPS / 10000);
            $pension = (int) round($gross * self::PENSION_BPS / 10000);
            $net = $gross - $paye - $ni - $pension;

            $run->payslips()->create([
                'employee_id' => $employee->id,
                'gross_pence' => $gross,
                'paye_pence' => $paye,
                'ni_pence' => $ni,
                'pension_pence' => $pension,
                'net_pence' => $net,
            ]);

            $totals['gross'] += $gross;
            $totals['paye'] += $paye;
            $totals['ni'] += $ni;
            $totals['pension'] += $pension;
            $totals['net'] += $net;
        }

        $journal = PostJournalEntry::run(
            [
                'date' => $payDate->toDateString(),
                'description' => 'Payroll '.$periodStart->format('F Y').' · '.$run->reference,
            ],
            [
                [
                    'account' => ChartOfAccounts::WAGES,
                    'description' => 'Gross pay for '.$run->period_start->format('F Y'),
                    'debit_pence' => $totals['gross'],
                ],
                [
                    'account' => ChartOfAccounts::PAYE_PAYABLE,
                    'description' => 'PAYE and NI due to HMRC',
                    'credit_pence' => $totals['paye'] + $totals['ni'],
                ],
                [
                    'account' => ChartOfAccounts::PENSION_PAYABLE,
                    'description' => 'Employee pension contributions',
                    'credit_pence' => $totals['pension'],
                ],
                [
                    'account' => ChartOfAccounts::BANK,
                    'description' => 'Net pay to employees',
                    'credit_pence' => $totals['net'],
                ],
            ],
            $run,
            $user,
        );

        $run->update([
            'gross_pence' => $totals['gross'],
            'paye_pence' => $totals['paye'],
            'ni_pence' => $totals['ni'],
            'pension_pence' => $totals['pension'],
            'net_pence' => $totals['net'],
            'journal_entry_id' => $journal->id,
        ]);

        return $run->fresh('payslips.employee', 'journalEntry');
    }
}
