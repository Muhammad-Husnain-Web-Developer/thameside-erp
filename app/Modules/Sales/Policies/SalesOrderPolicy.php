<?php

namespace App\Modules\Sales\Policies;

use App\Modules\Core\Models\User;

class SalesOrderPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('sales_orders.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('sales_orders.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('sales_orders.create');
    }

    public function invoice(User $actor): bool
    {
        return $actor->can('invoices.create');
    }
}
