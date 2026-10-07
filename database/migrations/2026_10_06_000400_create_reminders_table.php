<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20)->default('whatsapp');
            $table->string('type', 20); // 48h, 24h, 2h
            $table->timestampTz('scheduled_for');
            $table->timestampTz('sent_at')->nullable();
            $table->string('provider_message_id', 128)->nullable()->index();
            $table->string('delivery_status', 24)->default('scheduled'); // scheduled, sent, delivered, read, failed
            $table->string('interactive_action', 24)->nullable(); // confirm, reschedule, cancel
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->text('error_details')->nullable();
            $table->timestamps();

            $table->index(['scheduled_for', 'delivery_status']);
            $table->index(['booking_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
