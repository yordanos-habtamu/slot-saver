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
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('buffer_minutes')->default(10)->after('duration_minutes');
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('total_amount');
            $table->string('deposit_status', 20)->default('none')->after('deposit_amount'); // none, pending, paid, forfeited, refunded
            $table->timestampTz('deposit_paid_at')->nullable()->after('deposit_status');
            $table->decimal('risk_score', 5, 4)->nullable()->after('deposit_paid_at');
            $table->string('risk_tier', 16)->nullable()->after('risk_score'); // low, medium, high

            $table->index(['business_id', 'risk_tier']);
            $table->index(['deposit_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'risk_tier']);
            $table->dropIndex(['deposit_status']);
            $table->dropColumn([
                'buffer_minutes',
                'deposit_amount',
                'deposit_status',
                'deposit_paid_at',
                'risk_score',
                'risk_tier',
            ]);
        });
    }
};
