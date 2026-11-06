<?php

namespace App\Modules\Core\Policies;

use App\Modules\Core\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->id === $target->id || $actor->can('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can('users.update');
    }

    public function delete(User $actor, User $target): bool
    {
        if (! $actor->can('users.delete') || $actor->id === $target->id) {
            return false;
        }

        // Never leave the system without an administrator.
        return ! ($target->hasRole('admin') && User::role('admin')->count() <= 1);
    }
}
