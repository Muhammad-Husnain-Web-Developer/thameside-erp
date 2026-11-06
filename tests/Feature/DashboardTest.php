<?php

use App\Modules\Core\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the dashboard with workspace stats', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('stats', 4)
            ->where('stats.0.key', 'staff')
            ->where('can.manageUsers', true)
            ->where('company.currency', 'GBP')
            ->where('auth.user.roles.0', 'admin')
        );
});

it('only shows navigation items the user may access', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('navigation', 5)
            ->has('navigation.0.items', 1)
            ->where('navigation.0.items.0.label', 'Dashboard')
            ->has('navigation.1.items', 2)
            ->where('navigation.1.items.0.label', 'Products')
            ->where('navigation.2.label', 'Sales')
            ->has('navigation.2.items', 3)
            ->where('navigation.3.label', 'Insights')
            ->where('navigation.4.label', 'Reports')
            ->where('can.manageUsers', false)
        );
});

it('shows administration navigation to administrators', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->has('navigation', 9)
            ->where('navigation.1.label', 'Inventory')
            ->where('navigation.2.label', 'Purchasing')
            ->has('navigation.2.items', 3)
            ->where('navigation.3.label', 'Sales')
            ->has('navigation.3.items', 3)
            ->where('navigation.4.label', 'Insights')
            ->has('navigation.4.items', 1)
            ->where('navigation.5.label', 'Reports')
            ->has('navigation.5.items', 2)
            ->where('navigation.6.label', 'Accounting')
            ->has('navigation.6.items', 4)
            ->where('navigation.7.label', 'People')
            ->has('navigation.7.items', 2)
            ->where('navigation.8.label', 'Administration')
            ->has('navigation.8.items', 2)
        );
});

it('shares the configured company profile and tax display settings', function () {
    config([
        'erp.company.name' => 'Example Trading Ltd',
        'erp.currency' => 'GBP',
        'erp.currency_symbol' => '£',
        'erp.vat_rate_label' => '5%',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('company.name', 'Example Trading Ltd')
            ->where('company.currency', 'GBP')
            ->where('company.currency_symbol', '£')
            ->where('company.vat_rate_label', '5%')
        );
});
