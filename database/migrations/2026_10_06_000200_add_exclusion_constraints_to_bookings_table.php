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
            DB::statement("
                ALTER TABLE bookings
                ADD CONSTRAINT no_overlapping_staff_bookings
                EXCLUDE USING gist (
                    employee_user_id WITH =,
                    tstzrange(start_at, end_at, '[)') WITH &&
                )
                WHERE (status IN ('pending', 'confirmed') AND employee_user_id IS NOT NULL);
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE bookings DROP CONSTRAINT IF EXISTS no_overlapping_staff_bookings;');
        }
    }
};
