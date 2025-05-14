<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DldDocument extends Model
{
    protected $fillable = [
        'booking_id',
        'file_path',
        'uploaded_by',
    ];

    protected $hidden = ['file_path'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
