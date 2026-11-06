<?php

use App\Modules\Accounting\Http\Controllers\JournalController;
use App\Modules\Accounting\Http\Controllers\ReportController;
use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('accounting')->name('accounting.')->group(function () {
    Route::get('journals', [JournalController::class, 'index'])
        ->middleware('can:viewAny,'.JournalEntry::class)
        ->name('journals.index');

    Route::get('journals/create', [JournalController::class, 'create'])
        ->middleware('can:create,'.JournalEntry::class)
        ->name('journals.create');

    Route::post('journals', [JournalController::class, 'store'])
        ->middleware('can:create,'.JournalEntry::class)
        ->name('journals.store');

    Route::get('journals/{journal}', [JournalController::class, 'show'])
        ->middleware('can:view,journal')
        ->name('journals.show');

    Route::get('accounts', [ReportController::class, 'accounts'])
        ->middleware('permission:accounts.view')
        ->name('accounts.index');

    Route::get('reports/trial-balance', [ReportController::class, 'trialBalance'])
        ->middleware('permission:journals.view')
        ->name('reports.trial-balance');

    Route::get('reports/vat-return', [ReportController::class, 'vatReturn'])
        ->middleware('permission:journals.view')
        ->name('reports.vat-return');
});
