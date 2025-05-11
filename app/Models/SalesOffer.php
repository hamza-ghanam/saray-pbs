<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOffer extends Model
{
    protected $fillable = [
        'unit_id',
        'generated_by_id',
        'offer_date',
        'offer_price',
        'discount',
        'notes',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }
}
