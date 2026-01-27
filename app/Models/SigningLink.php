<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SigningLink extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_SIGNED    = 'signed';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Runtime-only token (NOT persisted).
     */
    public ?string $plain_token = null;

    protected $fillable = [
        // Context
        'signable_type',
        'signable_id',

        // Document
        'documentable_type',
        'documentable_id',

        // Recipient (one link per recipient)
        'recipient_email',
        'recipient_name',

        // Type & token
        'document_type',
        'token_hash',

        // State/audit
        'status',
        'expires_at',
        'signed_at',
        'client_ip',
        'user_agent',
    ];

    protected $casts = [
        'expires_at'    => 'datetime',
        'signed_at'     => 'datetime',
        'document_type' => DocumentType::class,
    ];

    public function signable(): MorphTo
    {
        return $this->morphTo();
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->token_hash)) {
                $plainToken = bin2hex(random_bytes(32));
                $model->token_hash = hash('sha256', $plainToken);
                $model->plain_token = $plainToken;
            }

            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }

            // Optional normalisation (recommended)
            if (!empty($model->recipient_email)) {
                $model->recipient_email = strtolower(trim($model->recipient_email));
            }
            if (!empty($model->recipient_name)) {
                $model->recipient_name = trim($model->recipient_name);
            }
        });
    }

    /* ---------------- Scopes ---------------- */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSigned(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SIGNED);
    }

    public function scopeForRecipient(Builder $query, string $email): Builder
    {
        return $query->where('recipient_email', strtolower(trim($email)));
    }

    public function scopeForDocumentType(Builder $query, DocumentType $type): Builder
    {
        return $query->where('document_type', $type->value);
    }

    /* ---------------- Helpers ---------------- */

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    public function markSigned(?string $ip = null, ?string $userAgent = null): void
    {
        $this->forceFill([
            'status'     => self::STATUS_SIGNED,
            'signed_at'  => now(),
            'client_ip'  => $ip,
            'user_agent' => $userAgent,
        ])->save();
    }

    public function markExpired(): void
    {
        $this->forceFill(['status' => self::STATUS_EXPIRED])->save();
    }

    public function markCancelled(): void
    {
        $this->forceFill(['status' => self::STATUS_CANCELLED])->save();
    }

    /**
     * On submit:
     * - set signed_at (audit)
     * - set status=expired to invalidate link immediately
     */
    public function consumeOnSubmit(?string $ip = null, ?string $userAgent = null): void
    {
        $this->forceFill([
            'signed_at'  => now(),
            'status'     => self::STATUS_EXPIRED,
            'client_ip'  => $ip,
            'user_agent' => $userAgent,
        ])->save();
    }
}