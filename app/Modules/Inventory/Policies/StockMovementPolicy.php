<?php

namespace App\Modules\Inventory\Policies;

use App\Modules\Core\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('stock.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('stock.adjust');
    }
}
