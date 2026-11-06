<?php

use App\Modules\Core\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('lists staff accounts for an administrator', function () {
    $admin = adminUser();
    User::factory()->create(['name' => 'Priya Shah'])->assignRole('sales');

    $this->actingAs($admin)
        ->get('/settings/users')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Users/Index')
            ->has('users.data', 2)
            ->where('can.create', true)
        );
});

it('filters staff accounts by search term', function () {
    $admin = adminUser();
    User::factory()->create(['name' => 'Priya Shah'])->assignRole('sales');
    User::factory()->create(['name' => 'Tomasz Nowak'])->assignRole('warehouse');

    $this->actingAs($admin)
        ->get('/settings/users?q=Priya')
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Priya Shah')
        );
});

it('forbids non administrators from viewing staff accounts', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->get('/settings/users')->assertForbidden();
})->with(['sales', 'warehouse', 'accountant']);

it('creates a staff account with a role', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post('/settings/users', [
            'name' => 'Grace Adeyemi',
            'email' => 'grace@thameside.test',
            'job_title' => 'Sales Administrator',
            'phone' => '+44 7700 900566',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'roles' => ['sales'],
            'is_active' => true,
        ])
        ->assertRedirect('/settings/users')
        ->assertSessionHas('success');

    $created = User::query()->where('email', 'grace@thameside.test')->firstOrFail();

    expect($created->hasRole('sales'))->toBeTrue()
        ->and($created->job_title)->toBe('Sales Administrator');
});

it('validates the new account payload', function () {
    $admin = adminUser();
    User::factory()->create(['email' => 'taken@thameside.test']);

    $this->actingAs($admin)
        ->post('/settings/users', [
            'name' => '',
            'email' => 'taken@thameside.test',
            'password' => 'short',
            'password_confirmation' => 'different',
            'roles' => [],
        ])
        ->assertSessionHasErrors(['name', 'email', 'password', 'roles']);
});

it('rejects unknown roles', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post('/settings/users', [
            'name' => 'Mallory',
            'email' => 'mallory@thameside.test',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'roles' => ['superuser'],
        ])
        ->assertSessionHasErrors('roles.0');
});

it('updates an account and swaps its role', function () {
    $admin = adminUser();
    $member = User::factory()->create(['name' => 'Old Name']);
    $member->assignRole('sales');

    $this->actingAs($admin)
        ->put("/settings/users/{$member->id}", [
            'name' => 'New Name',
            'email' => $member->email,
            'job_title' => 'Warehouse Supervisor',
            'roles' => ['warehouse'],
            'is_active' => true,
        ])
        ->assertRedirect('/settings/users');

    $member->refresh();

    expect($member->name)->toBe('New Name')
        ->and($member->hasRole('warehouse'))->toBeTrue()
        ->and($member->hasRole('sales'))->toBeFalse();
});

it('keeps the existing password when the field is left blank', function () {
    $admin = adminUser();
    $member = User::factory()->create();
    $member->assignRole('sales');
    $originalHash = $member->password;

    $this->actingAs($admin)
        ->put("/settings/users/{$member->id}", [
            'name' => $member->name,
            'email' => $member->email,
            'roles' => ['sales'],
            'is_active' => false,
        ])
        ->assertRedirect('/settings/users');

    $member->refresh();

    expect($member->password)->toBe($originalHash)
        ->and($member->is_active)->toBeFalse();
});

it('soft deletes a staff account and keeps the audit trail', function () {
    $admin = adminUser();
    $member = User::factory()->create();
    $member->assignRole('sales');

    $this->actingAs($admin)
        ->delete("/settings/users/{$member->id}")
        ->assertRedirect('/settings/users');

    $this->assertSoftDeleted('users', ['id' => $member->id]);
});

it('stops an administrator from deleting their own account', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->delete("/settings/users/{$admin->id}")
        ->assertForbidden();

    $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
});

it('forbids sales staff from creating accounts', function () {
    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales)->get('/settings/users/create')->assertForbidden();

    $this->actingAs($sales)
        ->post('/settings/users', [
            'name' => 'Sneaky',
            'email' => 'sneaky@thameside.test',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'roles' => ['admin'],
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'sneaky@thameside.test']);
});
