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
        'document_path',
        'start_date',
        'expiry_date',
        'email',
        'phone_number',
        'address',
    ];

    protected $hidden = ['document_path'];

    public function booking()
    {
        return $this->hasOne(Booking::class, 'customer_info_id');
    }
}
