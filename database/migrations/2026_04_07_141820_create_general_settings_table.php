<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();

            $table->string('group')->default('general'); // system, booking, notifications, etc.
            $table->string('key')->unique();             // unique setting key
            $table->text('value')->nullable();           // stored as string/json
            $table->string('type')->default('string');   // string, integer, boolean, json, float
            $table->text('description')->nullable();

            $table->boolean('is_public')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
