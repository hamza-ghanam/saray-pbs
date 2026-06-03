<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('installment_id')
                ->constrained('installments')
                ->cascadeOnDelete();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            $table->date('payment_date');
            $table->decimal('amount', 15, 2);

            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'cheque',
                'card',
                'other',
            ])->nullable();

            $table->string('reference_number')->nullable(); // transfer / cheque number

            $table->string('receipt_path')->nullable(); // customer payment receipt

            $table->enum('status', [
                'pending_verification',
                'verified',
                'rejected',
                'refunded',
            ])->default('pending_verification');

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

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

            // Indexes
            $table->index('installment_id');
            $table->index('booking_id');
            $table->index('status');
            $table->index(['booking_id', 'status']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
    }
};
