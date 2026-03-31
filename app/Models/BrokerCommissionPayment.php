<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrokerCommissionPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'broker_commission_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'receipt_path',
        'notes',
        'paid_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'broker_commission_id' => 'integer',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'paid_by' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(BrokerCommission::class, 'broker_commission_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
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
