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
            $table->string('name');
            $table->string('passport_number');
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->string('nationality');
            $table->string('document_path')->nullable();
            $table->date('start_date');
            $table->date('expiry_date');
            $table->string('email');
            $table->string('phone_number');
            $table->string('address');
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
