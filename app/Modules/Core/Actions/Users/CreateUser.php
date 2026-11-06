<?php

namespace App\Modules\Core\Actions\Users;

use App\Modules\Core\Actions\Action;
use App\Modules\Core\Models\User;

class CreateUser extends Action
{
    /**
     * @param  array{name: string, email: string, password: string, job_title?: ?string, phone?: ?string, is_active?: bool, roles: array<int, string>}  $data
     */
    public function handle(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'job_title' => $data['job_title'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->syncRoles($data['roles'] ?? []);

        return $user;
    }
}
