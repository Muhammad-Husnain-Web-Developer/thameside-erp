<?php

namespace App\Modules\Sales\Policies;

use App\Modules\Core\Models\User;

class InvoicePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('invoices.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('invoices.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('invoices.create');
    }

    public function pay(User $actor): bool
    {
        return $actor->can('invoices.pay');
    }
}
