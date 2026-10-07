<?php

use App\Http\Controllers\Admin\BusinessController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('businesses', [BusinessController::class, 'index'])->name('businesses.index');
    Route::post('businesses', [BusinessController::class, 'store'])->name('businesses.store');
    Route::get('businesses/{business}', [BusinessController::class, 'show'])->name('businesses.show');
});
