<?php

namespace App\Modules\Core\Support;

use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Shapes activity log rows for the dashboard and audit screens.
 */
final class ActivityFeed
{
    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<int, array<string, mixed>>
     */
    public static function map(Collection $activities): array
    {
        return $activities->map(fn (Activity $activity) => [
            'id' => $activity->id,
            'log' => $activity->log_name,
            'event' => $activity->event ?? $activity->description,
            'description' => $activity->description,
            'subject' => $activity->subject_type ? class_basename($activity->subject_type) : null,
            'subject_id' => $activity->subject_id,
            'causer' => $activity->causer?->name,
            'changes' => array_keys($activity->properties['attributes'] ?? []),
            'created_at' => $activity->created_at?->toIso8601String(),
            'created_human' => $activity->created_at?->diffForHumans(),
        ])->all();
    }
}
