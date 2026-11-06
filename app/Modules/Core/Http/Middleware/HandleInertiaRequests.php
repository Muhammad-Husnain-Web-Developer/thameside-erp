<?php

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Support\Navigation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Shared props available to every page.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'initials' => $user->initials(),
                    'email' => $user->email,
                    'job_title' => $user->job_title,
                    'roles' => $user->getRoleNames()->all(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->all(),
                ] : null,
            ],
            'company' => [
                ...config('erp.company'),
                'currency' => config('erp.currency'),
                'currency_symbol' => config('erp.currency_symbol'),
                'vat_rate_label' => config('erp.vat_rate_label'),
            ],
            'navigation' => Navigation::forUser($user),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
