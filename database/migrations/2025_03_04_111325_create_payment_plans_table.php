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
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "70/30", "50/50", "60/40", or custom.
            $table->decimal('selling_price', 15, 2);
            $table->decimal('dld_fee_percentage', 15, 2);
            $table->decimal('dld_fee', 15, 2);
            $table->decimal('admin_fee', 15, 2);
            $table->decimal('EOI', 15, 2)->default(100000);
            $table->decimal('booking_percentage', 5, 2);    // e.g., 20
            $table->decimal('handover_percentage', 5, 2);   // e.g., 30, 50, or 40
            $table->decimal('construction_percentage', 5, 2); // remainder: 60/30/40 respectively
            $table->date('first_construction_installment_date')->nullable();
            $table->boolean('isDefault')->default(false);
            $table->json('blocks');
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};
