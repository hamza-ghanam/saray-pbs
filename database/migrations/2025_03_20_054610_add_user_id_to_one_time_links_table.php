<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('one_time_links', function (Blueprint $table) {
            // Add a nullable unsignedBigInteger user_id field after the expired_at column.
            $table->unsignedBigInteger('user_id')->nullable()->after('expired_at');
            // Set up a foreign key constraint to the users table.
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otls', function (Blueprint $table) {
            //
        });
    }
};
