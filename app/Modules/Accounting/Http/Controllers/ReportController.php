<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\NominalAccount;
use App\Modules\Accounting\Support\ChartOfAccounts;
use App\Modules\Accounting\Support\Ledger;
use App\Modules\Core\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function accounts(Request $request): Response
    {
        $this->authorize('viewAny', NominalAccount::class);

        $balances = Ledger::accountBalances()->groupBy('type');

        $groups = collect(NominalAccount::TYPES)->map(fn (string $label, string $type) => [
            'type' => $type,
            'label' => $label,
            'accounts' => ($balances->get($type) ?? collect())->values()->map(fn (array $row) => [
                'code' => $row['code'],
                'name' => $row['name'],
                'debit' => Money::format($row['debit']),
                'credit' => Money::format($row['credit']),
                'balance' => Money::format(abs($row['balance'])),
                'side' => $row['balance'] === 0 ? '—' : ($row['balance'] > 0 ? 'Dr' : 'Cr'),
            ])->all(),
        ])->values();

        return Inertia::render('Accounting/Accounts/Index', [
            'groups' => $groups,
            'totals' => [
                'debit' => Money::format((int) $balances->flatten(1)->sum('debit')),
                'credit' => Money::format((int) $balances->flatten(1)->sum('credit')),
            ],
        ]);
    }

    public function trialBalance(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $to = $request->filled('to') ? Carbon::parse($request->string('to')) : null;
        $report = Ledger::trialBalance($to);

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'rows' => array_map(fn (array $row) => [
                'code' => $row['code'],
                'name' => $row['name'],
                'type' => $row['type'],
                'debit' => $row['debit'] > 0 ? Money::format($row['debit']) : null,
                'credit' => $row['credit'] > 0 ? Money::format($row['credit']) : null,
            ], $report['rows']),
            'totals' => [
                'debit' => Money::format($report['total_debit']),
                'credit' => Money::format($report['total_credit']),
            ],
            'balanced' => $report['balanced'],
            'filters' => ['to' => $request->string('to')->toString()],
        ]);
    }

    public function vatReturn(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $to = $request->filled('to') ? Carbon::parse($request->string('to')) : now();
        $from = $request->filled('from') ? Carbon::parse($request->string('from')) : $to->copy()->startOfQuarter();

        $summary = Ledger::vatSummary($from, $to);
        $entries = JournalEntry::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->with('lines.account')
            ->orderBy('date')
            ->get()
            ->flatMap(fn (JournalEntry $entry) => $entry->lines
                ->filter(fn ($line) => in_array($line->account?->code, [ChartOfAccounts::VAT_PAYABLE, ChartOfAccounts::VAT_RECEIVABLE], true))
                ->map(fn ($line) => [
                    'id' => $entry->id,
                    'number' => $entry->number,
                    'date' => $entry->date?->format('j M Y'),
                    'description' => $entry->description,
                    'account' => $line->account?->name,
                    'vat' => Money::format($line->debit_pence > 0 ? -$line->debit_pence : $line->credit_pence),
                    'direction' => $line->account?->code === ChartOfAccounts::VAT_PAYABLE ? 'Output' : 'Input',
                ]))
            ->values();

        return Inertia::render('Accounting/Reports/VatReturn', [
            'period' => [
                'from' => $from->format('j M Y'),
                'to' => $to->format('j M Y'),
                'from_input' => $from->toDateString(),
                'to_input' => $to->toDateString(),
                'label' => 'VAT quarter',
            ],
            'summary' => [
                'output_vat' => Money::format($summary['output_vat']),
                'input_vat' => Money::format($summary['input_vat']),
                'net_due' => Money::format($summary['net_due']),
                'sales_net' => Money::format($summary['sales_net']),
                'purchases_net' => Money::format($summary['purchases_net']),
                'sales_net_pence' => $summary['sales_net'],
                'purchases_net_pence' => $summary['purchases_net'],
                'output_vat_pence' => $summary['output_vat'],
                'input_vat_pence' => $summary['input_vat'],
                'net_due_pence' => $summary['net_due'],
                'is_repayment' => $summary['net_due'] < 0,
            ],
            'boxes' => [
                ['box' => '1', 'label' => 'VAT due on sales and other outputs', 'value' => Money::format($summary['output_vat'])],
                ['box' => '2', 'label' => 'VAT due on acquisitions from EU member states', 'value' => Money::format(0)],
                ['box' => '3', 'label' => 'Total VAT due (boxes 1 + 2)', 'value' => Money::format($summary['output_vat'])],
                ['box' => '4', 'label' => 'VAT reclaimed on purchases and other inputs', 'value' => Money::format($summary['input_vat'])],
                ['box' => '5', 'label' => 'Net VAT to pay to HMRC or reclaim', 'value' => Money::format(abs($summary['net_due']))],
                ['box' => '6', 'label' => 'Total value of sales excluding VAT', 'value' => Money::format($summary['sales_net'])],
                ['box' => '7', 'label' => 'Total value of purchases excluding VAT', 'value' => Money::format($summary['purchases_net'])],
                ['box' => '8', 'label' => 'Total value of supplies from other EU member states', 'value' => Money::format(0)],
                ['box' => '9', 'label' => 'Total value of acquisitions from other EU member states', 'value' => Money::format(0)],
            ],
            'entries' => $entries,
        ]);
    }
}
