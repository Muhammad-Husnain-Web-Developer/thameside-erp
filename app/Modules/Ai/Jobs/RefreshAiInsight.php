<?php

namespace App\Modules\Ai\Jobs;

use App\Modules\Ai\Services\InsightSummariser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued retry used when the model provider rate-limits us. Running on the
 * database queue with a backoff keeps the request path fast and the insight
 * fresh a few minutes later.
 */
class RefreshAiInsight implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    /**
     * @param  array<string, mixed>  $facts
     */
    public function __construct(public array $facts)
    {
    }

    public function handle(InsightSummariser $summariser): void
    {
        $summariser->generate($this->facts, force: true);
    }
}
