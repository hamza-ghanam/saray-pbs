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
        Schema::table('signing_links', function (Blueprint $table) {
            $table->timestamp('withdrawn_at')->nullable()->index()->after('signature_image_path');
            $table->unsignedBigInteger('withdrawn_by')
                ->nullable()
                ->after('withdrawn_at')
                ->index();

            $table->foreign('withdrawn_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->text('withdraw_reason')->nullable()->after('withdrawn_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signing_links', function (Blueprint $table) {
            //
        });
    }
};
