<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Actions\PostJournalEntry;
use App\Modules\Accounting\Http\Requests\StoreJournalRequest;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\NominalAccount;
use App\Modules\Core\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JournalEntry::class);

        $search = trim((string) $request->string('q'));

        $entries = JournalEntry::query()
            ->with('author')
            ->withCount('lines')
            ->withSum('lines as debit_total', 'debit_pence')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->latest('date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (JournalEntry $entry) => [
                'id' => $entry->id,
                'number' => $entry->number,
                'date' => $entry->date?->format('j M Y'),
                'description' => $entry->description,
                'source' => $entry->sourceLabel(),
                'lines' => $entry->lines_count,
                'total' => Money::format((int) $entry->debit_total),
                'author' => $entry->author?->name ?? 'System',
            ]);

        return Inertia::render('Accounting/Journals/Index', [
            'entries' => $entries,
            'filters' => ['q' => $search],
            'stats' => $this->stats(),
            'can' => [
                'create' => $request->user()->can('create', JournalEntry::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', JournalEntry::class);

        return Inertia::render('Accounting/Journals/Create', [
            'accounts' => $this->accountOptions(),
            'today' => now()->toDateString(),
        ]);
    }

    public function store(StoreJournalRequest $request): RedirectResponse
    {
        $entry = PostJournalEntry::run(
            [
                'date' => $request->validated('date'),
                'description' => $request->validated('description'),
            ],
            $request->validated('lines'),
            null,
            $request->user(),
        );

        return redirect()
            ->route('accounting.journals.show', $entry)
            ->with('success', "Journal {$entry->number} posted.");
    }

    public function show(JournalEntry $journal): Response
    {
        $this->authorize('view', $journal);

        $journal->load(['lines.account', 'author']);

        return Inertia::render('Accounting/Journals/Show', [
            'entry' => [
                'id' => $journal->id,
                'number' => $journal->number,
                'date' => $journal->date?->format('j F Y'),
                'description' => $journal->description,
                'source' => $journal->sourceLabel(),
                'author' => $journal->author?->name ?? 'System',
                'posted_at' => $journal->posted_at?->format('j M Y, H:i'),
                'total' => Money::format($journal->totalDebitPence()),
                'balanced' => $journal->isBalanced(),
            ],
            'lines' => $journal->lines->map(fn ($line) => [
                'id' => $line->id,
                'code' => $line->account?->code,
                'account' => $line->account?->name,
                'type' => $line->account?->typeLabel(),
                'description' => $line->description,
                'debit' => $line->debit_pence > 0 ? Money::format($line->debit_pence) : null,
                'credit' => $line->credit_pence > 0 ? Money::format($line->credit_pence) : null,
            ]),
        ]);
    }

    /**
     * @return array<int, array{value: int, label: string, type: string}>
     */
    private function accountOptions(): array
    {
        return NominalAccount::query()
            ->orderBy('code')
            ->get()
            ->map(fn (NominalAccount $account) => [
                'value' => $account->id,
                'label' => "{$account->code} · {$account->name}",
                'type' => $account->typeLabel(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string, value: string|int, meta: string, icon: string}>
     */
    private function stats(): array
    {
        $month = JournalEntry::query()
            ->where('date', '>=', now()->startOfMonth())
            ->withSum('lines as debit_total', 'debit_pence');

        return [
            [
                'key' => 'entries',
                'label' => 'Journals posted',
                'value' => number_format(JournalEntry::query()->count()),
                'meta' => 'Every document posts through the ledger',
                'icon' => 'history',
            ],
            [
                'key' => 'month',
                'label' => 'Posted this month',
                'value' => number_format((clone $month)->count()),
                'meta' => now()->format('F Y'),
                'icon' => 'chart',
            ],
            [
                'key' => 'value',
                'label' => 'Value this month',
                'value' => Money::format((int) (clone $month)->get()->sum('debit_total')),
                'meta' => 'Total debits posted',
                'icon' => 'banknotes',
            ],
            [
                'key' => 'accounts',
                'label' => 'Nominal accounts',
                'value' => number_format(NominalAccount::query()->count()),
                'meta' => 'Chart of accounts',
                'icon' => 'building',
            ],
        ];
    }
}
