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
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('preferred_employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('preferred_date');
            $table->time('preferred_time_from')->nullable();
            $table->time('preferred_time_to')->nullable();
            $table->string('status', 24)->default('waiting');
            $table->string('claim_token', 64)->nullable()->unique();
            $table->timestampTz('offered_at')->nullable();
            $table->timestampTz('offer_expires_at')->nullable();
            $table->foreignId('freed_booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'status', 'preferred_date']);
            $table->index(['service_id', 'status']);
            $table->index(['client_user_id', 'status']);
            $table->index(['status', 'offer_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
