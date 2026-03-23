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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ref_id'); // References Unit or Booking
            $table->string('ref_type')->comment('Unit, Booking');           // 'Unit' or 'Booking'
            $table->foreignId('approved_by')->constrained('users');
            $table->string('approval_type')->comment('CSO, Accountant, CFO, CEO');
            $table->enum('status', ['Pending', 'Approved', 'Rejected']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
