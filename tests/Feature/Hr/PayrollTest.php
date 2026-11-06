<?php

use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Accounting\Support\Ledger;
use App\Modules\Core\Models\User;
use App\Modules\Hr\Actions\RunPayroll;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\PayrollRun;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    ChartOfAccounts::ensure();

    $this->employee = Employee::create([
        'employee_number' => 'EMP-9001',
        'first_name' => 'Test',
        'last_name' => 'Worker',
        'job_title' => 'Warehouse operative',
        'contract_type' => 'full_time',
        'started_on' => '2025-01-06',
        'annual_salary_pence' => 3600000,
        'is_active' => true,
    ]);
});

it('runs monthly payroll and posts a balanced journal', function () {
    $run = RunPayroll::run(['month' => '2026-10']);

    expect($run->reference)->toBe('PR-202610')
        ->and($run->employee_count)->toBe(1)
        ->and($run->gross_pence)->toBe(300000)
        ->and($run->payslips)->toHaveCount(1)
        ->and($run->journal_entry_id)->not->toBeNull();

    $payslip = $run->payslips->first();

    expect($payslip->gross_pence)->toBe(300000)
        ->and($payslip->paye_pence)->toBe(39040)
        ->and($payslip->ni_pence)->toBe(15616)
        ->and($payslip->pension_pence)->toBe(15000)
        ->and($payslip->net_pence)->toBe(300000 - 39040 - 15616 - 15000);

    expect(Ledger::balancePence(ChartOfAccounts::WAGES))->toBe(300000)
        ->and(Ledger::balancePence(ChartOfAccounts::PAYE_PAYABLE))->toBe(-54656)
        ->and(Ledger::balancePence(ChartOfAccounts::PENSION_PAYABLE))->toBe(-15000)
        ->and(Ledger::balancePence(ChartOfAccounts::BANK))->toBe(-230344)
        ->and(Ledger::trialBalance()['balanced'])->toBeTrue();
});

it('refuses to run the same payroll period twice', function () {
    RunPayroll::run(['month' => '2026-10']);

    expect(fn () => RunPayroll::run(['month' => '2026-10']))
        ->toThrow(ValidationException::class);

    expect(PayrollRun::count())->toBe(1);
});

it('excludes employees who had not started yet', function () {
    Employee::create([
        'employee_number' => 'EMP-9002',
        'first_name' => 'Future',
        'last_name' => 'Starter',
        'job_title' => 'Designer',
        'contract_type' => 'full_time',
        'started_on' => '2027-01-04',
        'annual_salary_pence' => 4200000,
        'is_active' => true,
    ]);

    $run = RunPayroll::run(['month' => '2026-10']);

    expect($run->employee_count)->toBe(1);
});

it('refuses payroll when there is nobody to pay', function () {
    $this->employee->update(['is_active' => false]);

    expect(fn () => RunPayroll::run(['month' => '2026-10']))
        ->toThrow(ValidationException::class);
});

it('lets an accountant run payroll through the endpoint', function () {
    $accountant = User::factory()->create();
    $accountant->assignRole('accountant');

    $this->actingAs($accountant)
        ->post('/people/payroll', ['month' => '2026-10'])
        ->assertRedirect();

    expect(PayrollRun::count())->toBe(1);
});

it('forbids sales staff from running payroll', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales)
        ->post('/people/payroll', ['month' => '2026-10'])
        ->assertForbidden();

    expect(PayrollRun::count())->toBe(0);
});
