<?php

use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

// Inbound Messaging Webhooks (Public & Idempotent)
Route::prefix('webhooks')->group(function () {
    Route::get('whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
    Route::post('whatsapp', [WhatsAppWebhookController::class, 'handle'])->name('webhooks.whatsapp.handle');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
