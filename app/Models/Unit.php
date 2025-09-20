<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_AVAILABLE = 'Available';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_PRE_BOOKED = 'Pre-Booked';
    public const STATUS_BOOKED = 'Booked';
    public const STATUS_PRE_HOLD = 'Pre-Hold';
    public const STATUS_HOLD = 'Hold';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_PROCESSED = 'Processed';

    protected $fillable = [
        'prop_type',
        'unit_type',
        'unit_no',
        'floor',
        'parking',
        'amenity',
        'internal_square',
        'external_square',
        'furnished',
        'unit_view',
        'price',
        'min_price',
        'pre_lunch_price',
        'lunch_price',
        'building_id',
        'status_changed_at',
        'status',
        'floor_plan',
    ];

    protected $hidden = ['floor_plan'];

    protected $appends = [
        'internal_square_m',
        'external_square_m',
        'total_square_m',
        'total_square',
    ];

    protected $casts = [
        'building_id' => 'integer',
    ];

    protected const SQFT_TO_SQM = 1 / 10.7639;

    public function getTotalSquareAttribute(): float
    {
        return ($this->internal_square ?? 0) + ($this->external_square ?? 0);
    }

    public function getInternalSquareMAttribute(): float
    {
        return round(($this->internal_square ?? 0) * self::SQFT_TO_SQM, 2);
    }

    public function getExternalSquareMAttribute(): float
    {
        return round(($this->external_square ?? 0) * self::SQFT_TO_SQM, 2);
    }

    public function getTotalSquareMAttribute(): float
    {
        return round(
            (($this->internal_square ?? 0) + ($this->external_square ?? 0))
            * self::SQFT_TO_SQM,
            2);
    }

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
