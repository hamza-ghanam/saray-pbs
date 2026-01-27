<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signing_links', function (Blueprint $table) {
            $table->id();

            // Polymorphic owner/context (Booking, BrokerAgreement, etc.)
            $table->morphs('signable');

            // Polymorphic document being signed (RF, SPA, UserDoc, etc.)
            $table->morphs('documentable');

            // Who is expected to sign (one link per recipient)
            $table->string('recipient_email', 191);
            $table->string('recipient_name', 191)->nullable();

            // Document type (RF, SPA, BROKER_AGREEMENT, ...)
            $table->string('document_type', 50);

            // Token hash (SHA-256 = 64 hex chars)
            $table->char('token_hash', 64)->unique();

            // Status
            $table->string('status', 20)->default('pending');

            // Lifecycle
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('signed_at')->nullable();

            // Audit
            $table->string('client_ip', 45)->nullable(); // IPv4/IPv6
            $table->text('user_agent')->nullable();

            // Signature image path
            $table->string('signature_image_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Helpful indexes
            $table->index(['document_type', 'status']);
            $table->index('expires_at');
            $table->index('recipient_email');

            // Prevent multiple pending links for same recipient on same document
            $table->unique(
                ['documentable_type', 'documentable_id', 'recipient_email', 'status'],
                'uniq_doc_recipient_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signing_links');
    }
};