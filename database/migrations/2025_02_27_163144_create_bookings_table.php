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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['Pre-Booked', 'RF Pending', 'SPA Pending', 'Booked', 'Cancelled', 'Completed']);
            $table->decimal('discount', 15, 2)->default(0);
            $table->string('receipt_path')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->foreignId('agent_id')->nullable()->constrained('users');
            $table->foreignId('sale_source')->nullable()->constrained('users');
            $table->string('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
