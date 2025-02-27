<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationForm extends Model
{
    protected $fillable = ['booking_id', 'file_path', 'status'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
