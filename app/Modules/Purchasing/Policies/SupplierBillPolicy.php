<?php

namespace App\Modules\Purchasing\Policies;

use App\Modules\Core\Models\User;

class SupplierBillPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('bills.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('bills.view');
    }

    public function pay(User $actor): bool
    {
        return $actor->can('bills.pay');
    }
}
