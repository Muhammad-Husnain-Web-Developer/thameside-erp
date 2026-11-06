<?php

namespace App\Modules\Core\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Modules\Core\Actions\Users\CreateUser;
use App\Modules\Core\Actions\Users\DeleteUser;
use App\Modules\Core\Actions\Users\UpdateUser;
use App\Modules\Core\Http\Requests\Users\StoreUserRequest;
use App\Modules\Core\Http\Requests\Users\UpdateUserRequest;
use App\Modules\Core\Models\User;
use App\Modules\Core\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->string('q'));

        $users = User::query()
            ->with('roles')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'initials' => $user->initials(),
                'email' => $user->email,
                'job_title' => $user->job_title,
                'phone' => $user->phone,
                'roles' => $user->getRoleNames()->all(),
                'is_active' => $user->is_active,
                'last_login_human' => $user->last_login_at?->diffForHumans() ?? 'Never',
                'can' => [
                    'update' => $request->user()->can('update', $user),
                    'delete' => $request->user()->can('delete', $user),
                ],
            ]);

        return Inertia::render('Settings/Users/Index', [
            'users' => $users,
            'filters' => ['q' => $search],
            'can' => [
                'create' => $request->user()->can('create', User::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Settings/Users/Create', [
            'roles' => PermissionRegistry::assignableRoles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = CreateUser::run($request->validated());

        return redirect()
            ->route('settings.users.index')
            ->with('success', "{$user->name} can now sign in with their Thameside account.");
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Settings/Users/Edit', [
            'staff' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'job_title' => $user->job_title,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'roles' => $user->getRoleNames()->all(),
            ],
            'roles' => PermissionRegistry::assignableRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        UpdateUser::run($user, $request->validated());

        return redirect()
            ->route('settings.users.index')
            ->with('success', "{$user->name}'s account has been updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        DeleteUser::run($user);

        return redirect()
            ->route('settings.users.index')
            ->with('success', "{$user->name} has been removed from the team.");
    }
}
