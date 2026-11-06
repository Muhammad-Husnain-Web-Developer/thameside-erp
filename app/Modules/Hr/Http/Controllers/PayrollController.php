<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Money;
use App\Modules\Hr\Actions\RunPayroll;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Models\Payslip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PayrollRun::class);

        $runs = PayrollRun::query()
            ->with('journalEntry')
            ->latest('period_start')
            ->paginate(12)
            ->through(fn (PayrollRun $run) => [
                'id' => $run->id,
                'reference' => $run->reference,
                'period' => $run->period_start?->format('F Y'),
                'pay_date' => $run->pay_date?->format('j M Y'),
                'employees' => $run->employee_count,
                'gross' => Money::format($run->gross_pence),
                'deductions' => Money::format($run->paye_pence + $run->ni_pence + $run->pension_pence),
                'net' => Money::format($run->net_pence),
                'journal' => $run->journalEntry?->number,
            ]);

        return Inertia::render('People/Payroll/Index', [
            'runs' => $runs,
            'stats' => [
                [
                    'key' => 'headcount',
                    'label' => 'On payroll',
                    'value' => Employee::query()->where('is_active', true)->where('annual_salary_pence', '>', 0)->count(),
                    'meta' => 'Active salaried staff',
                    'icon' => 'users',
                ],
                [
                    'key' => 'last',
                    'label' => 'Last payroll',
                    'value' => Money::format((int) PayrollRun::query()->latest('period_start')->value('gross_pence')),
                    'meta' => PayrollRun::query()->latest('period_start')->value('reference') ?? 'Not yet run',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'runs',
                    'label' => 'Runs posted',
                    'value' => PayrollRun::query()->count(),
                    'meta' => 'All time',
                    'icon' => 'history',
                ],
                [
                    'key' => 'next',
                    'label' => 'Next pay date',
                    'value' => now()->endOfMonth()->format('j M Y'),
                    'meta' => 'Current month end',
                    'icon' => 'chart',
                ],
            ],
            'can' => ['run' => $request->user()->can('run', PayrollRun::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('run', PayrollRun::class);

        $employees = Employee::query()
            ->where('is_active', true)
            ->where('annual_salary_pence', '>', 0)
            ->get();

        return Inertia::render('People/Payroll/Create', [
            'month' => now()->format('Y-m'),
            'employees' => $employees->map(fn (Employee $employee) => [
                'name' => $employee->fullName(),
                'department' => $employee->department?->name,
                'gross' => Money::format($employee->monthlyGrossPence()),
            ]),
            'estimatedGross' => Money::format((int) $employees->sum(fn (Employee $employee) => $employee->monthlyGrossPence())),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('run', PayrollRun::class);

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'pay_date' => ['nullable', 'date'],
        ]);

        $run = RunPayroll::run($data, $request->user());

        return redirect()
            ->route('people.payroll.show', $run)
            ->with('success', "Payroll {$run->reference} posted to the ledger.");
    }

    public function show(Request $request, PayrollRun $payroll): Response
    {
        $this->authorize('view', $payroll);

        $payroll->load(['payslips.employee.department', 'journalEntry']);

        return Inertia::render('People/Payroll/Show', [
            'run' => [
                'id' => $payroll->id,
                'reference' => $payroll->reference,
                'period' => $payroll->period_start?->format('F Y'),
                'period_start' => $payroll->period_start?->format('j M Y'),
                'period_end' => $payroll->period_end?->format('j M Y'),
                'pay_date' => $payroll->pay_date?->format('j M Y'),
                'employees' => $payroll->employee_count,
                'gross' => Money::format($payroll->gross_pence),
                'paye' => Money::format($payroll->paye_pence),
                'ni' => Money::format($payroll->ni_pence),
                'pension' => Money::format($payroll->pension_pence),
                'net' => Money::format($payroll->net_pence),
                'journal' => $payroll->journalEntry ? [
                    'id' => $payroll->journalEntry->id,
                    'number' => $payroll->journalEntry->number,
                ] : null,
            ],
            'payslips' => $payroll->payslips->map(fn (Payslip $payslip) => [
                'id' => $payslip->id,
                'employee' => $payslip->employee?->fullName(),
                'number' => $payslip->employee?->employee_number,
                'department' => $payslip->employee?->department?->name,
                'gross' => Money::format($payslip->gross_pence),
                'paye' => Money::format($payslip->paye_pence),
                'ni' => Money::format($payslip->ni_pence),
                'pension' => Money::format($payslip->pension_pence),
                'net' => Money::format($payslip->net_pence),
            ]),
        ]);
    }
}
