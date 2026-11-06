<?php

namespace App\Modules\Hr\Policies;

use App\Modules\Core\Models\User;

class PayrollRunPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('payroll.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('payroll.view');
    }

    public function run(User $actor): bool
    {
        return $actor->can('payroll.run');
    }
}
