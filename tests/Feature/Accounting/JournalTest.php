<?php

use App\Modules\Accounting\Actions\PostJournalEntry;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Accounting\Support\Ledger;
use App\Modules\Core\Models\User;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    ChartOfAccounts::ensure();
});

function simpleJournal(array $overrides = []): JournalEntry
{
    return PostJournalEntry::run(
        ['date' => '2026-11-01', 'description' => $overrides['description'] ?? 'Test journal'],
        $overrides['lines'] ?? [
            ['account' => ChartOfAccounts::BANK, 'debit' => '1,000.00'],
            ['account' => ChartOfAccounts::SALES, 'credit' => '1,000.00'],
        ],
    );
}

it('posts a balanced journal with both sides recorded', function () {
    $entry = simpleJournal();

    expect($entry->number)->toBe('JE-000001')
        ->and($entry->lines)->toHaveCount(2)
        ->and($entry->totalDebitPence())->toBe(100000)
        ->and($entry->totalCreditPence())->toBe(100000)
        ->and($entry->isBalanced())->toBeTrue()
        ->and($entry->posted_at)->not->toBeNull();
});

it('numbers journals sequentially', function () {
    simpleJournal();

    $second = PostJournalEntry::run(
        ['description' => 'Second journal'],
        [
            ['account' => ChartOfAccounts::BANK, 'debit_pence' => 5000],
            ['account' => ChartOfAccounts::SALES, 'credit_pence' => 5000],
        ],
    );

    expect($second->number)->toBe('JE-000002');
});

it('refuses an unbalanced journal and writes nothing', function () {
    expect(fn () => simpleJournal([
        'lines' => [
            ['account' => ChartOfAccounts::BANK, 'debit' => '100.00'],
            ['account' => ChartOfAccounts::SALES, 'credit' => '90.00'],
        ],
    ]))->toThrow(ValidationException::class);

    expect(JournalEntry::count())->toBe(0);
});

it('refuses a line that is both a debit and a credit', function () {
    expect(fn () => simpleJournal([
        'lines' => [
            ['account' => ChartOfAccounts::BANK, 'debit' => '50.00', 'credit' => '50.00'],
            ['account' => ChartOfAccounts::SALES, 'credit' => '50.00'],
        ],
    ]))->toThrow(ValidationException::class);

    expect(JournalEntry::count())->toBe(0);
});

it('refuses a line with no value', function () {
    expect(fn () => simpleJournal([
        'lines' => [
            ['account' => ChartOfAccounts::BANK, 'debit' => '50.00'],
            ['account' => ChartOfAccounts::SALES, 'credit' => '50.00'],
            ['account' => ChartOfAccounts::OFFICE],
        ],
    ]))->toThrow(ValidationException::class);
});

it('refuses a journal with a single line', function () {
    expect(fn () => simpleJournal([
        'lines' => [
            ['account' => ChartOfAccounts::BANK, 'debit' => '50.00'],
        ],
    ]))->toThrow(ValidationException::class);

    expect(JournalEntry::count())->toBe(0);
});

it('keeps the trial balance at zero once entries are posted', function () {
    simpleJournal();

    PostJournalEntry::run(
        ['description' => 'Rent'],
        [
            ['account' => ChartOfAccounts::RENT, 'debit' => '325.00'],
            ['account' => ChartOfAccounts::BANK, 'credit' => '325.00'],
        ],
    );

    $trial = Ledger::trialBalance();

    expect($trial['balanced'])->toBeTrue()
        ->and($trial['total_debit'])->toBe($trial['total_credit'])
        ->and(Ledger::balancePence(ChartOfAccounts::BANK))->toBe(67500)
        ->and(Ledger::balancePence(ChartOfAccounts::SALES))->toBe(-100000);
});

it('lets an accountant post a journal through the endpoint', function () {
    $accountant = User::factory()->create();
    $accountant->assignRole('accountant');

    $this->actingAs($accountant)
        ->post('/accounting/journals', [
            'date' => '2026-11-02',
            'description' => 'Manual accrual',
            'lines' => [
                ['nominal_account_id' => ChartOfAccounts::id(ChartOfAccounts::RENT), 'debit' => '250.00'],
                ['nominal_account_id' => ChartOfAccounts::id(ChartOfAccounts::TRADE_CREDITORS), 'credit' => '250.00'],
            ],
        ])
        ->assertRedirect();

    $entry = JournalEntry::sole();

    expect($entry->description)->toBe('Manual accrual')
        ->and($entry->created_by)->toBe($accountant->id)
        ->and($entry->totalDebitPence())->toBe(25000);
});

it('rejects an unbalanced journal submitted through the endpoint', function () {
    $accountant = User::factory()->create();
    $accountant->assignRole('accountant');

    $this->actingAs($accountant)
        ->from('/accounting/journals/create')
        ->post('/accounting/journals', [
            'date' => '2026-11-02',
            'description' => 'Out of balance',
            'lines' => [
                ['nominal_account_id' => ChartOfAccounts::id(ChartOfAccounts::BANK), 'debit' => '100.00'],
                ['nominal_account_id' => ChartOfAccounts::id(ChartOfAccounts::SALES), 'credit' => '80.00'],
            ],
        ])
        ->assertRedirect('/accounting/journals/create')
        ->assertSessionHasErrors('lines');

    expect(JournalEntry::count())->toBe(0);
});

it('forbids sales staff from posting journals', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales)
        ->post('/accounting/journals', [
            'date' => '2026-11-02',
            'description' => 'Should not post',
            'lines' => [
                ['nominal_account_id' => ChartOfAccounts::id(ChartOfAccounts::BANK), 'debit' => '10.00'],
                ['nominal_account_id' => ChartOfAccounts::id(ChartOfAccounts::SALES), 'credit' => '10.00'],
            ],
        ])
        ->assertForbidden();

    expect(JournalEntry::count())->toBe(0);
});
