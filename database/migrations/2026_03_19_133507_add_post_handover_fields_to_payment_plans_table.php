<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->boolean('post_handover_enabled')
                ->default(false)
                ->after('handover_percentage');

            $table->unsignedInteger('post_handover_months')
                ->default(0)
                ->after('post_handover_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn([
                'post_handover_enabled',
                'post_handover_months',
            ]);
        });
    }
};
