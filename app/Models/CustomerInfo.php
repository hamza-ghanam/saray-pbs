<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInfo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'passport_number',
        'birth_date',
        'gender',
        'nationality',
        'start_date',
        'expiry_date',
        'email',
        'phone_number',
        'address',
        'document_path',
    ];

    protected $hidden = ['document_path'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
