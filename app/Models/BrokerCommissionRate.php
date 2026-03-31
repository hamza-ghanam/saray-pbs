<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class BrokerCommissionRate extends Model
{
    protected $fillable = [
        'percentage',
        'effective_from',
        'effective_to',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function commissions(): HasMany
    {
        return $this->hasMany(BrokerCommission::class, 'commission_rate_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeEffectiveAt(Builder $query, CarbonInterface $at): Builder
    {
        return $query
            ->where('effective_from', '<=', $at)
            ->where(function ($query) use ($at) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $at);
            });
    }
}
