<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
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
        'price'
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class, 'ref_id')->where('ref_type', 'Unit');
    }

    public function salesOffers()
    {
        return $this->hasMany(SalesOffer::class);
    }

    public function unitUpdates()
    {
        return $this->hasMany(UnitUpdate::class);
    }
}
