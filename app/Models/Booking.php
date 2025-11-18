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
        'discount',
        'price',
        'receipt_path',
        'created_by', // Make sure it's fillable
        'payment_plan_id',
        'agent_id',
        'sale_source_id',
        'agency_com_agent',
    ];

    protected $hidden = ['receipt_path'];

    protected $casts = [
        'created_by' => 'integer',
        'payment_plan_id' => 'integer',
        'customer_info_id' => 'integer',
        'unit_id' => 'integer',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function customerInfos()
    {
        return $this->hasMany(CustomerInfo::class);
    }

    public function reservationForm()
    {
        return $this->hasOne(ReservationForm::class);
    }

    // returns the signed form
    public function signedReservationForm()
    {
        return $this->hasOne(ReservationForm::class)
            ->whereNotNull('signed_at');
    }

    // returns the signed SPA
    public function signedSpa()
    {
        return $this->hasOne(SPA::class)
            ->whereNotNull('signed_at');
    }

    public function spa()
    {
        return $this->hasOne(SPA::class);
    }

    public function approvals()
    {
        return $this->morphMany(
            Approval::class,
            'approvalable',  // matches the morphTo() name in Approval
            'ref_type',      // column holding the class name
            'ref_id'         // column holding the ID
        );
    }

    public function getLatestApprovedAtAttribute()
    {
        return $this->approvals()
            ->where('status', 'Approved')
            ->latest()
            ->value('created_at');
    }

    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class, 'payment_plan_id');
    }

    /**
     * Each booking gets its own set of installments
     */
    public function installments()
    {
        return $this->hasMany(Installment::class)
            ->orderBy('date');
    }

    public function dldDocument()
    {
        return $this->hasOne(DldDocument::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function saleSource()
    {
        return $this->belongsTo(User::class, 'sale_source_id');
    }
}
