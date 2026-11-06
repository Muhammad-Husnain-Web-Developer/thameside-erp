<?php

use App\Modules\Core\Http\Controllers\Auth\LoginController;
use App\Modules\Core\Http\Controllers\DashboardController;
use App\Modules\Core\Http\Controllers\Settings\ActivityLogController;
use App\Modules\Core\Http\Controllers\Settings\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::resource('users', UserController::class)
            ->except(['show'])
            ->middlewareFor('index', 'permission:users.view')
            ->middlewareFor('create', 'permission:users.create')
            ->middlewareFor('store', 'permission:users.create')
            ->middlewareFor('edit', 'permission:users.update')
            ->middlewareFor('update', 'permission:users.update')
            ->middlewareFor('destroy', 'permission:users.delete');

        Route::get('activity', [ActivityLogController::class, 'index'])
            ->middleware('permission:activity.view')
            ->name('activity.index');
    });
});
