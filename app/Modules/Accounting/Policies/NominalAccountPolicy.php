<?php

namespace App\Modules\Accounting\Policies;

use App\Modules\Core\Models\User;

class NominalAccountPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('accounts.view');
    }
}
