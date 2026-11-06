<?php

use App\Modules\Sales\Http\Controllers\CustomerController;
use App\Modules\Sales\Http\Controllers\InvoiceController;
use App\Modules\Sales\Http\Controllers\SalesOrderController;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('sales')->name('sales.')->group(function () {
    Route::get('customers', [CustomerController::class, 'index'])
        ->middleware('can:viewAny,'.Customer::class)
        ->name('customers.index');

    Route::get('customers/create', [CustomerController::class, 'create'])
        ->middleware('can:create,'.Customer::class)
        ->name('customers.create');

    Route::post('customers', [CustomerController::class, 'store'])
        ->middleware('can:create,'.Customer::class)
        ->name('customers.store');

    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])
        ->middleware('can:update,customer')
        ->name('customers.edit');

    Route::put('customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('can:update,customer')
        ->name('customers.update');

    Route::get('orders', [SalesOrderController::class, 'index'])
        ->middleware('can:viewAny,'.SalesOrder::class)
        ->name('orders.index');

    Route::get('orders/create', [SalesOrderController::class, 'create'])
        ->middleware('can:create,'.SalesOrder::class)
        ->name('orders.create');

    Route::post('orders', [SalesOrderController::class, 'store'])
        ->middleware('can:create,'.SalesOrder::class)
        ->name('orders.store');

    Route::get('orders/{order}', [SalesOrderController::class, 'show'])
        ->middleware('can:view,order')
        ->name('orders.show');

    Route::post('orders/{order}/invoice', [SalesOrderController::class, 'invoice'])
        ->middleware('can:invoice,order')
        ->name('orders.invoice');

    Route::get('invoices', [InvoiceController::class, 'index'])
        ->middleware('can:viewAny,'.Invoice::class)
        ->name('invoices.index');

    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])
        ->middleware('can:view,invoice')
        ->name('invoices.show');

    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->middleware('can:view,invoice')
        ->name('invoices.pdf');

    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])
        ->middleware('can:pay,invoice')
        ->name('invoices.pay');
});
