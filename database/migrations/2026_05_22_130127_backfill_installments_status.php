<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // installment of cancelled booking → waived
        DB::statement("
            UPDATE installments i
            INNER JOIN bookings b ON b.id = i.booking_id
            SET i.status = 'waived'
            WHERE b.status = 'Cancelled'
              AND i.deleted_at IS NULL
        ");

        // remaining: if overdue date → overdue, otherwise → pending
        DB::statement("
            UPDATE installments
            SET status = CASE
                WHEN date < CURDATE() THEN 'overdue'
                ELSE 'pending'
            END
            WHERE status = 'pending'
              AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        // Reset to default
        DB::table('installments')->update(['status' => 'pending', 'paid_amount' => 0]);
    }
};
