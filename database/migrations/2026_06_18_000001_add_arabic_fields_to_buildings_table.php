<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('location_ar')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'location_ar']);
        });
    }
};
