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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('prop_type');
            $table->string('unit_type');
            $table->string('unit_no');
            $table->string('floor');
            $table->string('parking');
            $table->string('pool_jacuzzi');
            $table->decimal('suite_area', 10, 2);
            $table->decimal('balcony_area', 10, 2);
            $table->decimal('total_area', 10, 2);
            $table->boolean('furnished');
            $table->string('unit_view');
            $table->string('floor_plan')->nullable();
            $table->decimal('price', 10, 2);
            $table->date('completion_date')->nullable();
            // System
            $table->foreignId('building_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['Pending', 'Available', 'Pre-Booked', 'Booked', 'Sold', 'Pre-Hold', 'Hold', 'Cancelled']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
