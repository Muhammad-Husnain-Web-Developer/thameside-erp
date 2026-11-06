<?php

namespace App\Modules\Core\Actions\Users;

use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;

class DeleteUser extends Action
{
    /**
     * Soft delete a staff account. The activity log keeps the audit trail.
     */
    public function handle(User $user): void
    {
        $user->delete();
    }
}
