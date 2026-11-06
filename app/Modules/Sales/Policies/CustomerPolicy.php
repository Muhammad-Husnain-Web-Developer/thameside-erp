<?php

namespace App\Modules\Sales\Policies;

use App\Modules\Core\Models\User;

class CustomerPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('customers.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('customers.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('customers.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('customers.update');
    }
}
