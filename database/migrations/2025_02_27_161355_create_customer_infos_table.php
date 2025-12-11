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
        Schema::create('customer_infos', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('address_en');
            $table->string('address_ar');
            $table->string('passport_number');
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->string('nationality_en');
            $table->string('nationality_ar');
            $table->string('document_path');
            $table->date('issuance_date');
            $table->date('expiry_date');
            $table->string('email');
            $table->string('phone_number');
            $table->string('emirates_id_number')->nullable();
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_infos');
    }
};
