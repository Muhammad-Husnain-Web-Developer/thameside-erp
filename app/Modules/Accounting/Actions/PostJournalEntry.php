<?php

namespace App\Modules\Accounting\Actions;

use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\NominalAccount;
use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * The single posting engine. Every journal in the ERP - manual, invoice,
 * supplier bill, payment - goes through here so a document can never post a
 * one-sided entry.
 */
class PostJournalEntry extends Action
{
    /**
     * @param  array{date?: string, description: string}  $data
     * @param  array<int, array{nominal_account_id?: int|string, account?: string, description?: ?string, debit?: mixed, credit?: mixed, debit_pence?: mixed, credit_pence?: mixed}>  $lines
     */
    public function handle(array $data, array $lines, ?Model $source = null, ?User $user = null): JournalEntry
    {
        $prepared = $this->prepareLines($lines);
        $this->assertBalanced($prepared);

        $entry = JournalEntry::create([
            'number' => $this->nextNumber(),
            'date' => isset($data['date']) ? Carbon::parse($data['date']) : now(),
            'description' => $data['description'],
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'posted_at' => now(),
            'created_by' => $user?->id ?? auth()->id(),
        ]);

        foreach ($prepared as $line) {
            $entry->lines()->create($line);
        }

        return $entry->load('lines.account');
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array{nominal_account_id: int, description: ?string, debit_pence: int, credit_pence: int}>
     */
    private function prepareLines(array $lines): array
    {
        $prepared = [];

        foreach ($lines as $index => $line) {
            $accountId = $line['nominal_account_id'] ?? null;

            if (! $accountId && isset($line['account'])) {
                $accountId = NominalAccount::query()->where('code', $line['account'])->value('id');
            }

            if (! $accountId) {
                throw ValidationException::withMessages([
                    "lines.{$index}.nominal_account_id" => 'Choose a nominal account for every line.',
                ]);
            }

            if (! NominalAccount::query()->whereKey($accountId)->exists()) {
                throw ValidationException::withMessages([
                    "lines.{$index}.nominal_account_id" => 'That nominal account does not exist.',
                ]);
            }

            $debit = $this->toPence($line['debit_pence'] ?? $line['debit'] ?? 0);
            $credit = $this->toPence($line['credit_pence'] ?? $line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.debit" => 'Debits and credits cannot be negative.',
                ]);
            }

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.debit" => 'A line is either a debit or a credit, never both.',
                ]);
            }

            if ($debit === 0 && $credit === 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}.debit" => 'Enter a debit or a credit on every line.',
                ]);
            }

            $prepared[] = [
                'nominal_account_id' => (int) $accountId,
                'description' => $line['description'] ?? null,
                'debit_pence' => $debit,
                'credit_pence' => $credit,
            ];
        }

        if (count($prepared) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'A journal needs at least two lines.',
            ]);
        }

        return $prepared;
    }

    /**
     * @param  array<int, array{debit_pence: int, credit_pence: int}>  $lines
     */
    private function assertBalanced(array $lines): void
    {
        $debits = array_sum(array_column($lines, 'debit_pence'));
        $credits = array_sum(array_column($lines, 'credit_pence'));

        if ($debits === 0 && $credits === 0) {
            throw ValidationException::withMessages([
                'lines' => 'A journal cannot be posted with no value.',
            ]);
        }

        if ($debits !== $credits) {
            throw ValidationException::withMessages([
                'lines' => 'The journal does not balance: debits '.Money::format($debits).' against credits '.Money::format($credits).'.',
            ]);
        }
    }

    private function toPence(mixed $value): int
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? 0 : Money::parse($value);
        }

        return (int) ($value ?? 0);
    }

    private function nextNumber(): string
    {
        $latest = JournalEntry::query()->orderByDesc('id')->value('number');
        $next = $latest && preg_match('/(\d+)$/', $latest, $matches) ? ((int) $matches[1]) + 1 : 1;

        return config('erp.prefixes.journal', 'JE').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
