<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the pivot table
        Schema::create('booking_customer_info', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_info_id')->constrained()->onDelete('cascade');
            $table->boolean('requires_signature')->default(true);
            $table->timestamps();
            $table->unique(['booking_id', 'customer_info_id']);
        });

        // 2. Backfill pivot from existing customer_infos data
        DB::table('customer_infos')
            ->whereNotNull('booking_id')
            ->chunkById(500, function ($rows) {
                DB::table('booking_customer_info')->insertOrIgnore(
                    collect($rows)->map(fn($r) => [
                        'booking_id'         => $r->booking_id,
                        'customer_info_id'   => $r->id,
                        'requires_signature' => $r->requires_signature ?? 1,
                        'created_at'         => $r->created_at,
                        'updated_at'         => $r->updated_at,
                    ])->all()
                );
            });

        // 3. Drop old columns from customer_infos
        $fkName = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customer_infos'
              AND COLUMN_NAME = 'booking_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ")?->CONSTRAINT_NAME;

        Schema::table('customer_infos', function (Blueprint $table) use ($fkName) {
            if ($fkName) {
                $table->dropForeign($fkName);
            }
            $table->dropColumn(['booking_id', 'requires_signature']);
        });
    }

    public function down(): void
    {
        // Restore the columns
        Schema::table('customer_infos', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('requires_signature')->default(true)->after('address_ar');
        });

        // Backfill the columns from pivot
        DB::table('booking_customer_info')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('customer_infos')
                    ->where('id', $row->customer_info_id)
                    ->update([
                        'booking_id'         => $row->booking_id,
                        'requires_signature' => $row->requires_signature,
                    ]);
            }
        });

        Schema::dropIfExists('booking_customer_info');
    }
};
