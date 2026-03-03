<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class UserSignature extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'image_path',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Return absolute storage path (for PDF embedding).
     */
    public function absolutePath(): string
    {
        return Storage::path($this->image_path);
    }

    /**
     * Delete file from storage when model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (UserSignature $signature) {
            if (!empty($signature->image_path)) {
                Storage::delete($signature->image_path);
            }
        });
    }
}
