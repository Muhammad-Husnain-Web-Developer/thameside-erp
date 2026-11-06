<?php

namespace App\Modules\Accounting\Policies;

use App\Modules\Core\Models\User;

class JournalEntryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('journals.view');
    }

    public function view(User $actor): bool
    {
        return $actor->can('journals.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('journals.create');
    }
}
