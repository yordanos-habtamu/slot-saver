<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Extension may be shared by other tables; intentionally keep or drop if needed.
    }
};
