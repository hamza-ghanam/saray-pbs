<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'unit_id',
        'customer_info_id',
        'status',
        'receipt_path',
        'confirmed_by',
        'confirmed_at'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function customerInfo()
    {
        return $this->belongsTo(CustomerInfo::class);
    }

    public function reservationForm()
    {
        return $this->hasOne(ReservationForm::class);
    }

    public function spa()
    {
        return $this->hasOne(SPA::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class, 'ref_id')->where('ref_type', 'Booking');
    }
}
