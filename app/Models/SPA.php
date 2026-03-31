<?php

namespace App\Models;

use App\Contracts\Documents\SignableDocument;
use Illuminate\Database\Eloquent\Model;

class SPA extends Model implements SignableDocument
{
    protected $table = 'spas';
    protected $fillable = [
        'booking_id',
        'file_path',
        'status',
        'signed_at',
        'signed_file_path',
        'company_signed_at'
    ];

    protected $hidden = ['file_path', 'signed_file_path'];

    protected $casts = [
        'company_signed_at' => 'datetime',
        'signed_at'         => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function approvals()
    {
        return $this->morphMany(
            \App\Models\Approval::class,
            'approvalable',
            'ref_type',
            'ref_id'
        );
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
        $base = 'SPA_' . $this->booking_id;
        return match ($variant) {
            'original' => $base . '_ORIGINAL.pdf',
            'signed'   => $base . '_SIGNED.pdf',
            default    => $base . '.pdf',
        };
    }
}
