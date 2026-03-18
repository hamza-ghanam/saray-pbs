<?php

namespace App\Models;

use App\Enums\BrokerCommissionStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrokerCommission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_id',
        'broker_user_id',
        'commission_rate_id',
        'commission_percentage',
        'base_amount',
        'commission_amount',
        'paid_amount',
        'remaining_amount',
        'status',
        'eligible_at',
        'calculated_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'broker_user_id' => 'integer',
        'commission_rate_id' => 'integer',
        'commission_percentage' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'status' => BrokerCommissionStatusEnum::class,
        'eligible_at' => 'datetime',
        'calculated_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'broker_user_id');
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(BrokerCommissionRate::class, 'commission_rate_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BrokerCommissionPayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
