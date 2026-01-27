<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_id',
        'file_path',
        'status',
        'signed_at',
        'signed_file_path',
    ];

    protected $hidden = ['file_path', 'signed_file_path'];

    protected $casts = [
        'unit_id' => 'integer',
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

    public function getOriginalPdfPath(): ?string
    {
        return $this->file_path;
    }

    public function getSignedPdfPath(): ?string
    {
        return $this->signed_file_path;
    }

    public function getDownloadFileName(string $variant = 'latest'): string
    {
        $base = 'RF_' . $this->booking_id;
        return match ($variant) {
            'original' => $base . '_ORIGINAL.pdf',
            'signed'   => $base . '_SIGNED.pdf',
            default    => $base . '.pdf',
        };
    }
}
