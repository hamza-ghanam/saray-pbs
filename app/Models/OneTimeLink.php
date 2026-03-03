<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OneTimeLink extends Model
{
    use SoftDeletes;

    protected $fillable = ['token', 'user_type', 'expired_at', 'user_id'];

    protected $casts = [
        'expired_at' => 'datetime',
        'user_id' => 'integer',
    ];

    /**
     * Plain token (NOT stored in DB)
     * Used only right after creating() to email the user.
     */
    public ?string $plain_token = null;

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            // token stored in DB should be hashed
            if (empty($model->token)) {
                $plainToken = bin2hex(random_bytes(32));
                $model->token = hash('sha256', $plainToken);
                $model->plain_token = $plainToken;
            }

            // optional default status if you have it
            // if (empty($model->status)) {
            //     $model->status = self::STATUS_PENDING;
            // }

            // optional normalisation
            if (!empty($model->user_type)) {
                $model->user_type = trim((string) $model->user_type);
            }
        });
    }

    /**
     * Helper: check plain token against stored hash.
     */
    public function matchesPlainToken(string $plainToken): bool
    {
        return hash_equals($this->token, hash('sha256', $plainToken));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
