<?php

namespace App\Modules\Inventory\Policies;

use App\Modules\Core\Models\User;

class ProductPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('products.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('products.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('products.create');
    }

    public function update(User $actor): bool
    {
        return $actor->can('products.update');
    }

    public function delete(User $actor): bool
    {
        return $actor->can('products.delete');
    }
}
