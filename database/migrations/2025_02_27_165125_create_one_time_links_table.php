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
        Schema::create('one_time_links', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('user_type')->comment('Broker, Contractor');
            $table->timestamp('expired_at')->nullable();; // OTL expires after a user uses it
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('one_time_links');
    }
};
