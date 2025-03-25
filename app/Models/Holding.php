<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holding extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_id',
        'status',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class, 'ref_id')->where('ref_type', 'Booking');
    }
}
