<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PaymentPlan extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'unit_id',
        'name',
        'dld_fee_percentage',
        'admin_fee',
        'booking_percentage',
        'handover_percentage',
        'is_default',
        'blocks',
        'post_handover_enabled',
        'post_handover_months',
        'description',
        'percentage',
        'date',
        'amount',
    ];

    protected $casts = [
        'blocks' => 'array',
        'unit_id' => 'integer',
        'post_handover_enabled' => 'boolean',
        'post_handover_months' => 'integer',
        'is_default' => 'boolean',
    ];

    /**
     * Get the installments associated with this payment plan.
     */
    public function installments()
    {
        return $this->hasMany(Installment::class)
            ->orderBy('date');
    }

    /**
     * A PaymentPlan can be used by many Bookings.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'payment_plan_id');
    }

    public static function setDefault(int $id): void
    {
        DB::transaction(function () use ($id) {
            static::where('is_default', true)
                ->update(['is_default' => false]);

            static::where('id', $id)
                ->update(['is_default' => true]);
        });
    }

    public function calculateDldFee(float $price): float
    {
        $percentage = (float) $this->dld_fee_percentage;

        return round(
            $price * ($percentage / 100),
            2
        );
    }
}
