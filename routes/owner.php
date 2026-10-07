<?php

use App\Http\Controllers\Owner\LocationController;
use App\Http\Controllers\Owner\LocationEmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:owner,admin'])->prefix('owner')->name('owner.')->group(function (): void {
    Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
    Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
    Route::get('locations/{location}', [LocationController::class, 'show'])->name('locations.show');
    Route::put('locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::delete('locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

    Route::put('locations/{location}/hours', [LocationController::class, 'updateHours'])->name('locations.hours.update');
    Route::post('locations/{location}/closures', [LocationController::class, 'storeClosure'])->name('locations.closures.store');
    Route::delete('locations/{location}/closures/{closure}', [LocationController::class, 'destroyClosure'])->name('locations.closures.destroy');

    Route::post('locations/{location}/employees', [LocationEmployeeController::class, 'store'])->name('locations.employees.store');
    Route::delete('locations/{location}/employees/{user}', [LocationEmployeeController::class, 'destroy'])->name('locations.employees.destroy');
});
