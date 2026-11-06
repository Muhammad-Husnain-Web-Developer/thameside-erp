<?php

namespace App\Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ai\Services\AnomalyDetector;
use App\Modules\Ai\Services\DemandForecaster;
use App\Modules\Ai\Services\InsightSummariser;
use App\Modules\Core\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InsightController extends Controller
{
    public function index(Request $request, DemandForecaster $forecaster, AnomalyDetector $detector, InsightSummariser $summariser): Response
    {
        abort_unless($request->user()->can('ai.view'), 403);

        $forecast = $forecaster->suggestions();
        $anomalies = $detector->detect();

        $facts = [
            'month' => now()->format('F Y'),
            'urgent_count' => collect($forecast)->where('urgent', true)->count(),
            'suggested_spend' => Money::format((int) collect($forecast)->sum(fn (array $row) => (int) preg_replace('/[^\d]/', '', $row['suggested_cost']))),
            'anomaly_count' => count($anomalies),
            'reorder' => collect($forecast)->take(4)->map(fn (array $row) => [
                'product' => $row['name'],
                'cover' => $row['months_cover'],
                'suggested' => $row['suggested_order'],
            ])->all(),
            'anomalies' => collect($anomalies)->take(3)->pluck('title')->all(),
        ];

        $insight = $summariser->generate($facts);

        return Inertia::render('Ai/Insights/Index', [
            'insight' => $insight,
            'forecast' => $forecast,
            'anomalies' => $anomalies,
            'stats' => [
                [
                    'key' => 'reorder',
                    'label' => 'Reorder suggestions',
                    'value' => count($forecast),
                    'meta' => collect($forecast)->where('urgent', true)->count().' urgent',
                    'icon' => 'box',
                ],
                [
                    'key' => 'anomalies',
                    'label' => 'Anomalies flagged',
                    'value' => count($anomalies),
                    'meta' => 'Last 90 days',
                    'icon' => 'warning',
                ],
                [
                    'key' => 'spend',
                    'label' => 'Suggested spend',
                    'value' => Money::format((int) collect($forecast)->sum(fn (array $row) => (int) preg_replace('/[^\d]/', '', $row['suggested_cost']))),
                    'meta' => 'To reach three months cover',
                    'icon' => 'banknotes',
                ],
                [
                    'key' => 'source',
                    'label' => 'Analysis source',
                    'value' => $insight['source'] === 'model' ? 'Groq' : 'Built-in rules',
                    'meta' => $insight['model'] ?? 'Deterministic PHP analysis',
                    'icon' => 'sparkles',
                ],
            ],
        ]);
    }
}
