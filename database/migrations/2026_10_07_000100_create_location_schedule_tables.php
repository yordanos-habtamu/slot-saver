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
        Schema::create('location_opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = Monday .. 7 = Sunday (ISO-8601)
            $table->time('opens_at');
            $table->time('closes_at');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['location_id', 'day_of_week']);
            $table->index('day_of_week');
        });

        Schema::create('location_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable(); // null = single-day closure
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['location_id', 'starts_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_closures');
        Schema::dropIfExists('location_opening_hours');
    }
};
