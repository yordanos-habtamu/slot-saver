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
        Schema::table('users', function (Blueprint $table) {
            $table->text('preferences')->nullable()->after('address');
            $table->decimal('rating_average', 3, 2)->nullable()->after('preferences');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_average');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferences', 'rating_average', 'rating_count']);
        });
    }
};
