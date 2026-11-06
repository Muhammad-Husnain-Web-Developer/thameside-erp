<?php

namespace Database\Seeders;

use App\Modules\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoStaffSeeder extends Seeder
{
    /**
     * The Thameside team. Everyone signs in with the password "password".
     */
    public function run(): void
    {
        $team = [
            [
                'name' => 'Amelia Hart',
                'email' => 'amelia.hart@thamesidekitchen.co.uk',
                'job_title' => 'Operations Director',
                'phone' => '+44 7700 900112',
                'role' => 'admin',
            ],
            [
                'name' => 'Oliver Bennett',
                'email' => 'oliver.bennett@thamesidekitchen.co.uk',
                'job_title' => 'Management Accountant',
                'phone' => '+44 7700 900233',
                'role' => 'accountant',
            ],
            [
                'name' => 'Priya Shah',
                'email' => 'priya.shah@thamesidekitchen.co.uk',
                'job_title' => 'Sales Manager',
                'phone' => '+44 7700 900344',
                'role' => 'sales',
            ],
            [
                'name' => 'Tomasz Nowak',
                'email' => 'tomasz.nowak@thamesidekitchen.co.uk',
                'job_title' => 'Warehouse Supervisor',
                'phone' => '+44 7700 900455',
                'role' => 'warehouse',
            ],
            [
                'name' => 'Grace Adeyemi',
                'email' => 'grace.adeyemi@thamesidekitchen.co.uk',
                'job_title' => 'Sales Administrator',
                'phone' => '+44 7700 900566',
                'role' => 'sales',
            ],
            [
                'name' => 'Callum Reid',
                'email' => 'callum.reid@thamesidekitchen.co.uk',
                'job_title' => 'Warehouse Operative',
                'phone' => '+44 7700 900677',
                'role' => 'warehouse',
            ],
        ];

        foreach ($team as $member) {
            $user = User::query()->updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'job_title' => $member['job_title'],
                    'phone' => $member['phone'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$member['role']]);
        }
    }
}
