<?php

namespace App\Models;

use App\Contracts\Documents\SignableDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDoc extends Model implements SignableDocument
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'doc_type',
        'file_path',
        'signed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'signed_at' => 'datetime',
    ];

    /**
     * Get the user that owns this document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getOriginalPdfPath(): string
    {
        return $this->file_path;
    }

    public function getSignedPdfPath(): ?string
    {
        return $this->signed_file_path;
    }

    public function getDownloadFileName(string $variant = 'latest'): string
    {
        $base = 'BROKER_AGREEMENT_' . $this->user_id;
        return match ($variant) {
            'original' => $base . '_ORIGINAL.pdf',
            'signed'   => $base . '_SIGNED.pdf',
            default    => $base . '.pdf',
        };
    }
}

