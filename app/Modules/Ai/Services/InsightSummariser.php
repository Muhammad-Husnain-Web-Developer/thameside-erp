<?php

namespace App\Modules\Ai\Services;

use App\Modules\Ai\Jobs\RefreshAiInsight;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Turns the deterministic forecast/anomaly facts into two or three sentences
 * for the operations team. The prompt is deliberately small, results are
 * cached for an hour, and any failure falls back to a plain-English summary
 * written in PHP so the page always works without a key.
 */
class InsightSummariser
{
    private const CACHE_SECONDS = 3600;

    /**
     * @param  array<string, mixed>  $facts
     * @return array{source: string, text: string, generated_at: string, model: ?string}
     */
    public function generate(array $facts, bool $force = false): array
    {
        $key = 'ai:insight:'.md5(json_encode($facts));

        if (! $force && Cache::has($key)) {
            return Cache::get($key);
        }

        $fallback = [
            'source' => 'fallback',
            'text' => $this->fallbackText($facts),
            'generated_at' => now()->toIso8601String(),
            'model' => null,
        ];

        $apiKey = config('erp.ai.key');

        if (! $apiKey) {
            return $fallback;
        }

        try {
            $response = Http::baseUrl(rtrim((string) config('erp.ai.base_url'), '/'))
                ->withToken($apiKey)
                ->timeout(12)
                ->acceptJson()
                ->post('/chat/completions', [
                    'model' => config('erp.ai.model'),
                    'temperature' => 0.2,
                    'max_tokens' => 220,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an operations analyst for a London kitchen appliance wholesaler. Reply with two short sentences of practical advice. Use £ and British spelling. No lists, no headings.',
                        ],
                        [
                            'role' => 'user',
                            'content' => 'Facts: '.json_encode($facts, JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ])
                ->throw();

            $text = trim((string) data_get($response->json(), 'choices.0.message.content'));

            if ($text === '') {
                return $fallback;
            }

            $insight = [
                'source' => 'model',
                'text' => $text,
                'generated_at' => now()->toIso8601String(),
                'model' => (string) config('erp.ai.model'),
            ];

            Cache::put($key, $insight, self::CACHE_SECONDS);

            return $insight;
        } catch (RequestException $exception) {
            if ($exception->response?->status() === 429) {
                RefreshAiInsight::dispatch($facts)->delay(now()->addSeconds(30));

                return [
                    'source' => 'queued',
                    'text' => $this->fallbackText($facts),
                    'generated_at' => now()->toIso8601String(),
                    'model' => null,
                ];
            }

            report($exception);

            return $fallback;
        } catch (Throwable $exception) {
            report($exception);

            return $fallback;
        }
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    private function fallbackText(array $facts): string
    {
        $urgent = (int) ($facts['urgent_count'] ?? 0);
        $anomalies = (int) ($facts['anomaly_count'] ?? 0);
        $suggested = (string) ($facts['suggested_spend'] ?? '£0.00');

        $parts = [];

        $parts[] = $urgent > 0
            ? "{$urgent} stocked line(s) need reordering now, with about {$suggested} of suggested replenishment."
            : 'Stock cover looks healthy across the catalogue, with no urgent reorders.';

        $parts[] = $anomalies > 0
            ? "{$anomalies} ledger event(s) look unusual and are worth a quick review before month end."
            : 'No unusual stock movements, invoices or overdue debts were detected.';

        return implode(' ', $parts);
    }
}
