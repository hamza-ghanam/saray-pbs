<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'partially_paid',
                'paid',
                'overdue',
                'waived',
            ])->default('pending')->after('amount');

            $table->decimal('paid_amount', 15, 2)
                ->default(0.00)
                ->after('status');

            $table->index('status');
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex(['booking_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'paid_amount']);
        });
    }
};
