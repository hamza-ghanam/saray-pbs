<?php

use App\Enums\BrokerCommissionStatusEnum;
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
        Schema::create('broker_commissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->unique()
                ->constrained('bookings')
                ->cascadeOnDelete();

            $table->foreignId('broker_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('commission_rate_id')
                ->constrained('broker_commission_rates')
                ->restrictOnDelete();

            $table->decimal('commission_percentage', 5, 2);
            $table->decimal('base_amount', 15, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);

            $table->string('status')->default(BrokerCommissionStatusEnum::DUE->value);

            $table->dateTime('eligible_at');
            $table->dateTime('calculated_at');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('broker_user_id');
            $table->index('commission_rate_id');
            $table->index('status');
            $table->index('eligible_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broker_commissions');
    }
};
