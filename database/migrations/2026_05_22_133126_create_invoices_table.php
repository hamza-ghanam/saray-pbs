<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Sequential unique invoice number (e.g. INV-2026-00001)
            $table->string('invoice_number', 50)->unique();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            // Nullable — booking confirmation invoices are not tied to a specific payment
            $table->foreignId('installment_payment_id')
                ->nullable()
                ->constrained('installment_payments')
                ->nullOnDelete();

            $table->enum('type', [
                'booking_confirmation',
                'payment_receipt',
                'tax_invoice',
            ]);

            $table->date('issue_date');
            $table->date('due_date')->nullable();

            // Amounts
            $table->decimal('subtotal', 15, 2);
            $table->decimal('vat_rate', 5, 2)->default(0.00);
            $table->decimal('vat_amount', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2);

            $table->enum('status', [
                'draft',
                'issued',
                'cancelled',
            ])->default('draft');

            $table->string('pdf_path')->nullable();

            $table->foreignId('issued_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
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
            $table->index('booking_id');
            $table->index('installment_payment_id');
            $table->index('type');
            $table->index('status');
            $table->index('issue_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
