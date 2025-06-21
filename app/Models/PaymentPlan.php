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
        'EOI',
        'booking_percentage',
        'handover_percentage',
        'construction_percentage',
        'is_default',
        'blocks',
    ];

    protected $casts = [
        'blocks' => 'array',
        'unit_id' => 'integer',
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
}
