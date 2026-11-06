<?php

use App\Modules\Purchasing\Http\Controllers\PurchaseOrderController;
use App\Modules\Purchasing\Http\Controllers\SupplierBillController;
use App\Modules\Purchasing\Http\Controllers\SupplierController;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierBill;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('purchasing')->name('purchasing.')->group(function () {
    Route::get('suppliers', [SupplierController::class, 'index'])
        ->middleware('can:viewAny,'.Supplier::class)
        ->name('suppliers.index');

    Route::get('suppliers/create', [SupplierController::class, 'create'])
        ->middleware('can:create,'.Supplier::class)
        ->name('suppliers.create');

    Route::post('suppliers', [SupplierController::class, 'store'])
        ->middleware('can:create,'.Supplier::class)
        ->name('suppliers.store');

    Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])
        ->middleware('can:update,supplier')
        ->name('suppliers.edit');

    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('can:update,supplier')
        ->name('suppliers.update');

    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])
        ->middleware('can:viewAny,'.PurchaseOrder::class)
        ->name('purchase-orders.index');

    Route::get('purchase-orders/create', [PurchaseOrderController::class, 'create'])
        ->middleware('can:create,'.PurchaseOrder::class)
        ->name('purchase-orders.create');

    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])
        ->middleware('can:create,'.PurchaseOrder::class)
        ->name('purchase-orders.store');

    Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
        ->middleware('can:view,purchaseOrder')
        ->name('purchase-orders.show');

    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
        ->middleware('can:receive,purchaseOrder')
        ->name('purchase-orders.receive');

    Route::get('bills', [SupplierBillController::class, 'index'])
        ->middleware('can:viewAny,'.SupplierBill::class)
        ->name('bills.index');

    Route::get('bills/{bill}', [SupplierBillController::class, 'show'])
        ->middleware('can:view,bill')
        ->name('bills.show');

    Route::post('bills/{bill}/pay', [SupplierBillController::class, 'pay'])
        ->middleware('can:pay,bill')
        ->name('bills.pay');
});
