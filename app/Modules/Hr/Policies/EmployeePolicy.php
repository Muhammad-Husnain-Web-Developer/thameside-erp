<?php

namespace App\Modules\Hr\Policies;

use App\Modules\Core\Models\User;

class EmployeePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('hr.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('hr.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('hr.manage');
    }

    public function update(User $actor): bool
    {
        return $actor->can('hr.manage');
    }
}
