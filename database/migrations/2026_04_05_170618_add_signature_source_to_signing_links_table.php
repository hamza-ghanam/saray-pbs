<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('signing_links', function (Blueprint $table) {
            $table->string('signature_source', 30)
                ->nullable()
                ->after('signature_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('signing_links', function (Blueprint $table) {
            $table->dropColumn('signature_source');
        });
    }
};
