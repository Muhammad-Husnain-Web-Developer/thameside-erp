<?php

use App\Modules\Core\Actions\Users\CreateUser;
use App\Modules\Core\Actions\Users\UpdateUser;
use App\Modules\Core\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

function actor(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

it('logs the creation of a staff account', function () {
    $admin = actor();
    $this->actingAs($admin);

    $created = CreateUser::run([
        'name' => 'Grace Adeyemi',
        'email' => 'grace@thameside.test',
        'password' => 'secret-password',
        'job_title' => 'Sales Administrator',
        'roles' => ['sales'],
        'is_active' => true,
    ]);

    $activity = Activity::query()->where('log_name', 'user')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('created')
        ->and($activity->subject_type)->toBe(User::class)
        ->and($activity->subject_id)->toBe($created->id)
        ->and($activity->causer_id)->toBe($admin->id);
});

it('only logs the fields that actually changed', function () {
    $admin = actor();
    $this->actingAs($admin);

    $member = User::factory()->create(['name' => 'Old Name']);

    UpdateUser::run($member, [
        'name' => 'New Name',
        'email' => $member->email,
        'job_title' => $member->job_title,
        'phone' => $member->phone,
        'roles' => ['sales'],
        'is_active' => true,
    ]);

    $activity = Activity::query()->where('event', 'updated')->latest('id')->first();

    expect(array_keys($activity->properties['attributes']))->toContain('name')
        ->and(array_keys($activity->properties['attributes']))->not->toContain('email');
});

it('records sign ins in the audit trail', function () {
    $user = User::factory()->create();
    $user->assignRole('sales');

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $activity = Activity::query()->where('log_name', 'auth')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Signed in')
        ->and($activity->causer_id)->toBe($user->id);
});

it('shows the audit trail to permitted staff only', function () {
    $admin = actor();

    $this->actingAs($admin)
        ->get('/settings/activity')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Activity')
            ->has('activities.data')
            ->has('events')
        );

    $sales = User::factory()->create();
    $sales->assignRole('sales');

    $this->actingAs($sales)->get('/settings/activity')->assertForbidden();
});

it('filters the audit trail by event', function () {
    $admin = actor();
    $this->actingAs($admin);

    $member = User::factory()->create(['name' => 'Filter Target']);
    $member->assignRole('sales');
    UpdateUser::run($member, [
        'name' => 'Filter Target Renamed',
        'email' => $member->email,
        'roles' => ['sales'],
        'is_active' => true,
    ]);

    $this->get('/settings/activity?event=updated')
        ->assertInertia(fn (Assert $page) => $page
            ->has('activities.data', 1)
            ->where('activities.data.0.event', 'updated')
        );
});
