<?php

use App\Modules\Ai\Http\Controllers\InsightController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('ai')->name('ai.')->group(function () {
    Route::get('/', [InsightController::class, 'index'])
        ->middleware('permission:ai.view')
        ->name('index');
});
