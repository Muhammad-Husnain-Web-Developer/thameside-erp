<?php

namespace App\Modules\Core\Actions\Users;

use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;

class UpdateUser extends Action
{
    /**
     * @param  array{name: string, email: string, job_title?: ?string, phone?: ?string, is_active?: bool, roles: array<int, string>, password?: ?string}  $data
     */
    public function handle(User $user, array $data): User
    {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $user->syncRoles($data['roles'] ?? []);

        return $user;
    }
}
