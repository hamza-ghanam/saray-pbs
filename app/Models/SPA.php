<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SPA extends Model
{
    protected $table = 'spas';
    protected $fillable = [
        'booking_id',
        'file_path',
        'status',
        'signed_at',
        'signed_file_path',
    ];

    protected $hidden = ['file_path', 'signed_file_path'];

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
}
