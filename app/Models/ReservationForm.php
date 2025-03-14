<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationForm extends Model
{
    use SoftDeletes;

    protected $fillable = ['booking_id', 'file_path', 'status'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
