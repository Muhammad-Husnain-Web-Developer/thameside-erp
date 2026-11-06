<?php

use App\Modules\Reporting\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('reports')->name('reports.')->group(function () {
    Route::get('sales', [ReportController::class, 'salesSummary'])
        ->middleware('permission:reports.view')
        ->name('sales');

    Route::get('stock', [ReportController::class, 'stockValuation'])
        ->middleware('permission:reports.view')
        ->name('stock');

    Route::get('exports/invoices', [ReportController::class, 'exportInvoices'])
        ->middleware('permission:reports.export')
        ->name('exports.invoices');

    Route::get('exports/stock', [ReportController::class, 'exportStock'])
        ->middleware('permission:reports.export')
        ->name('exports.stock');

    Route::get('exports/purchase-orders', [ReportController::class, 'exportPurchaseOrders'])
        ->middleware('permission:reports.export')
        ->name('exports.purchase-orders');
});
