<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

// Public Client Booking Flow
Route::get('book/{slug?}', [BookingController::class, 'index'])->name('booking.index');
Route::get('booking/confirmation/{reference_code}', [BookingController::class, 'confirmation'])->name('booking.confirmation');

// Inbound Messaging Webhooks (Public & Idempotent)
Route::prefix('webhooks')->group(function () {
    Route::get('whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
    Route::post('whatsapp', [WhatsAppWebhookController::class, 'handle'])->name('webhooks.whatsapp.handle');
});

// Waitlist Auto-Fill Endpoints
Route::post('api/waitlist/join', [WaitlistController::class, 'join'])->middleware('auth')->name('waitlist.join');
Route::get('waitlist/claim/{token}', [WaitlistController::class, 'showOffer'])->name('waitlist.claim.show');
Route::post('waitlist/claim/{token}', [WaitlistController::class, 'claim'])->name('waitlist.claim.execute');

// Client & Staff API Endpoints
Route::prefix('api')->group(function () {
    Route::get('booking/available-slots', [BookingController::class, 'availableSlots'])->name('api.booking.available_slots');
    Route::post('booking/risk-preview', [BookingController::class, 'riskPreview'])->name('api.booking.risk_preview');
    Route::post('booking/store', [BookingController::class, 'store'])->name('api.booking.store');

    Route::post('offline/sync', [OfflineSyncController::class, 'sync'])->middleware('auth')->name('api.offline.sync');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
