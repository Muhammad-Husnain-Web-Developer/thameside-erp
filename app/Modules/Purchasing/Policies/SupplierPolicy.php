<?php

namespace App\Modules\Purchasing\Policies;

use App\Modules\Core\Models\User;

class SupplierPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('suppliers.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('suppliers.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('suppliers.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('suppliers.update');
    }
}
