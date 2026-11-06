<?php

use App\Modules\Hr\Http\Controllers\EmployeeController;
use App\Modules\Hr\Http\Controllers\PayrollController;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\PayrollRun;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('people')->name('people.')->group(function () {
    Route::get('employees', [EmployeeController::class, 'index'])
        ->middleware('can:viewAny,'.Employee::class)
        ->name('employees.index');

    Route::get('employees/create', [EmployeeController::class, 'create'])
        ->middleware('can:create,'.Employee::class)
        ->name('employees.create');

    Route::post('employees', [EmployeeController::class, 'store'])
        ->middleware('can:create,'.Employee::class)
        ->name('employees.store');

    Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])
        ->middleware('can:update,employee')
        ->name('employees.edit');

    Route::put('employees/{employee}', [EmployeeController::class, 'update'])
        ->middleware('can:update,employee')
        ->name('employees.update');

    Route::get('payroll', [PayrollController::class, 'index'])
        ->middleware('can:viewAny,'.PayrollRun::class)
        ->name('payroll.index');

    Route::get('payroll/create', [PayrollController::class, 'create'])
        ->middleware('can:run,'.PayrollRun::class)
        ->name('payroll.create');

    Route::post('payroll', [PayrollController::class, 'store'])
        ->middleware('can:run,'.PayrollRun::class)
        ->name('payroll.store');

    Route::get('payroll/{payroll}', [PayrollController::class, 'show'])
        ->middleware('can:view,payroll')
        ->name('payroll.show');
});
