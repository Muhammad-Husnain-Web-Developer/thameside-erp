<?php

namespace App\Modules\Purchasing\Policies;

use App\Modules\Core\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('purchase_orders.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('purchase_orders.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('purchase_orders.create');
    }

    public function receive(User $actor): bool
    {
        return $actor->can('purchase_orders.receive');
    }
}
