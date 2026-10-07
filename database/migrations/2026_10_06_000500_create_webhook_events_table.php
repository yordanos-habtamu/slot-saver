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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32); // whatsapp, twilio
            $table->string('idempotency_key', 128)->unique();
            $table->string('event_type', 64);
            $table->json('payload');
            $table->timestampTz('processed_at')->nullable();
            $table->string('response_status', 24)->default('success');
            $table->timestamps();

            $table->index(['provider', 'event_type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
