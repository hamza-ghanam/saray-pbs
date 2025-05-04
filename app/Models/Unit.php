<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'prop_type',
        'unit_type',
        'unit_no',
        'floor',
        'parking',
        'pool_jacuzzi',
        'suite_area',
        'balcony_area',
        'total_area',
        'furnished',
        'unit_view',
        'price',
        'completion_date',
        'building_id',
        'status_changed_at',
        'floor_plan',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function holdings()
    {
        return $this->hasMany(Holding::class);
    }

    public function approvals()
    {
        return $this->morphMany(
            \App\Models\Approval::class,
            'approvalable', // must match your Approval::morphTo() method name
            'ref_type',     // the column that holds the class name
            'ref_id'        // the column that holds the unit's ID
        );
    }

    public function salesOffers()
    {
        return $this->hasMany(SalesOffer::class);
    }

    public function paymentPlans()
    {
        return $this->hasMany(PaymentPlan::class);
    }

    public function unitUpdates()
    {
        return $this->hasMany(UnitUpdate::class);
    }

    /**
     * Relationship: Each unit belongs to a contractor (a User).
     */
    public function contractor()
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }

    /**
     * The most recent Holding in Pre-Hold or Hold.
     */
    public function latestHolding()
    {
        return $this->hasOne(Holding::class)
            ->whereIn('status', ['Pre-Hold','Hold', 'Processed'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * The most recent Booking not cancelled.
     */
    public function latestBooking()
    {
        return $this->hasOne(Booking::class)
            ->where('status', '!=', 'Cancelled')
            ->orderBy('created_at', 'desc');
    }
}
