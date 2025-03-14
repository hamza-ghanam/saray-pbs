<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_id',
        'customer_info_id',
        'status',
        'receipt_path',
        'confirmed_by',
        'confirmed_at',
        'created_by', // Make sure it's fillable
        'payment_plan_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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


    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class, 'payment_plan_id');
    }
}
