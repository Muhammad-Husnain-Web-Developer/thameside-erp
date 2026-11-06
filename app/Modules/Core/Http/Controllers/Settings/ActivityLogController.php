<?php

namespace App\Modules\Core\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\ActivityFeed;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('q'));
        $event = (string) $request->string('event');

        $activities = Activity::query()
            ->with('causer')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhereHas('causer', fn ($causer) => $causer->where('name', 'like', "%{$search}%"));
            }))
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Settings/Activity', [
            'activities' => [
                'data' => ActivityFeed::map($activities->getCollection()),
                'links' => $activities->linkCollection()->toArray(),
                'from' => $activities->firstItem(),
                'to' => $activities->lastItem(),
                'total' => $activities->total(),
            ],
            'filters' => ['q' => $search, 'event' => $event],
            'events' => Activity::query()
                ->select('event')
                ->whereNotNull('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event')
                ->all(),
        ]);
    }
}
