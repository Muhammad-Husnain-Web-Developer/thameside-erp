<?php

use App\Modules\Core\Models\User;

it('shows the sign in screen to guests', function () {
    $this->get('/login')->assertOk();
});

it('signs a staff member in and redirects to the dashboard', function () {
    $user = User::factory()->create(['email' => 'priya@thameside.test']);
    $user->assignRole('sales');

    $this->post('/login', [
        'email' => 'priya@thameside.test',
        'password' => 'password',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects an incorrect password', function () {
    User::factory()->create(['email' => 'priya@thameside.test']);

    $this->post('/login', [
        'email' => 'priya@thameside.test',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('blocks deactivated accounts', function () {
    User::factory()->inactive()->create(['email' => 'former@thameside.test']);

    $this->post('/login', [
        'email' => 'former@thameside.test',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('throttles repeated failed attempts', function () {
    User::factory()->create(['email' => 'priya@thameside.test']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => 'priya@thameside.test', 'password' => 'nope']);
    }

    $this->post('/login', [
        'email' => 'priya@thameside.test',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('signs a staff member out', function () {
    $user = User::factory()->create();
    $user->assignRole('sales');

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/login');

    $this->assertGuest();
});

it('redirects guests away from the dashboard', function () {
    $this->get('/')->assertRedirect('/login');
});

it('records the sign in time', function () {
    $user = User::factory()->create(['last_login_at' => null]);
    $user->assignRole('warehouse');

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});
