<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\ActivityFeed;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $activeStaff = User::query()->where('is_active', true)->count();
        $totalStaff = User::query()->count();
        $auditEvents = Activity::query()->where('created_at', '>=', now()->subDays(7))->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                [
                    'key' => 'staff',
                    'label' => 'Active staff',
                    'value' => $activeStaff,
                    'meta' => $totalStaff === $activeStaff
                        ? 'All accounts active'
                        : ($totalStaff - $activeStaff).' account(s) deactivated',
                    'icon' => 'users',
                ],
                [
                    'key' => 'roles',
                    'label' => 'Roles',
                    'value' => Role::query()->count(),
                    'meta' => Role::query()->orderBy('name')->pluck('name')->implode(', '),
                    'icon' => 'shield',
                ],
                [
                    'key' => 'permissions',
                    'label' => 'Permissions',
                    'value' => Permission::query()->count(),
                    'meta' => 'Enforced by policies on every route',
                    'icon' => 'key',
                ],
                [
                    'key' => 'activity',
                    'label' => 'Audit events',
                    'value' => $auditEvents,
                    'meta' => 'Recorded in the last 7 days',
                    'icon' => 'history',
                ],
            ],
            'recentActivity' => ActivityFeed::map(
                Activity::query()
                    ->with('causer')
                    ->latest()
                    ->limit(8)
                    ->get()
            ),
            'can' => [
                'manageUsers' => $request->user()->can('users.view'),
            ],
        ]);
    }
}
