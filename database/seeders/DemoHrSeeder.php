<?php

namespace Database\Seeders;

use App\Modules\Core\Models\User;
use App\Modules\Hr\Actions\RunPayroll;
use App\Modules\Hr\Models\Department;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\PayrollRun;
use Illuminate\Database\Seeder;

class DemoHrSeeder extends Seeder
{
    public function run(): void
    {
        if (Department::query()->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'amelia.hart@thamesidekitchen.co.uk')->first()
            ?? User::query()->first();

        $departments = collect([
            ['code' => 'DEP-WH', 'name' => 'Warehouse', 'description' => 'Goods in, picking and delivery.'],
            ['code' => 'DEP-SA', 'name' => 'Sales', 'description' => 'Trade counter and account management.'],
            ['code' => 'DEP-FI', 'name' => 'Finance', 'description' => 'Accounts, credit control and payroll.'],
            ['code' => 'DEP-OP', 'name' => 'Operations', 'description' => 'Buying, logistics and facilities.'],
        ])->mapWithKeys(fn (array $row) => [$row['name'] => Department::create($row + ['is_active' => true])]);

        $people = [
            ['EMP-0001', 'Amelia', 'Hart', 'amelia.hart@thamesidekitchen.co.uk', 'Operations', 'Managing Director', 'full_time', 6200000, '2021-03-01'],
            ['EMP-0002', 'Marcus', 'Bell', 'marcus.bell@thamesidekitchen.co.uk', 'Sales', 'Trade Sales Manager', 'full_time', 4150000, '2022-01-17'],
            ['EMP-0003', 'Priya', 'Raghavan', 'priya.raghavan@thamesidekitchen.co.uk', 'Finance', 'Management Accountant', 'full_time', 4650000, '2022-09-05'],
            ['EMP-0004', 'Tom', 'Whitfield', 'tom.whitfield@thamesidekitchen.co.uk', 'Warehouse', 'Warehouse Supervisor', 'full_time', 3200000, '2023-02-13'],
            ['EMP-0005', 'Grace', 'Okafor', 'grace.okafor@thamesidekitchen.co.uk', 'Sales', 'Account Manager', 'full_time', 3400000, '2023-06-19'],
            ['EMP-0006', 'Daniel', 'Mercer', 'daniel.mercer@thamesidekitchen.co.uk', 'Warehouse', 'Delivery Driver', 'full_time', 2900000, '2024-04-08'],
            ['EMP-0007', 'Sofia', 'Nunes', 'sofia.nunes@thamesidekitchen.co.uk', 'Finance', 'Purchase Ledger Clerk', 'part_time', 2100000, '2024-11-04'],
            ['EMP-0008', 'Owen', 'Price', 'owen.price@thamesidekitchen.co.uk', 'Warehouse', 'Apprentice Fitter', 'apprentice', 1650000, '2025-09-01'],
        ];

        foreach ($people as [$number, $first, $last, $email, $department, $title, $contract, $salary, $started]) {
            Employee::create([
                'employee_number' => $number,
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'phone' => '+44 20 7946 03'.str_pad((string) random_int(10, 99), 2, '0', STR_PAD_LEFT),
                'department_id' => $departments[$department]->id,
                'job_title' => $title,
                'contract_type' => $contract,
                'started_on' => $started,
                'annual_salary_pence' => $salary,
                'payroll_reference' => 'TKS-'.substr($number, -4),
                'is_active' => true,
            ]);
        }

        if (! PayrollRun::query()->exists()) {
            RunPayroll::run(['month' => now()->subMonth()->format('Y-m')], $admin);
        }
    }
}
