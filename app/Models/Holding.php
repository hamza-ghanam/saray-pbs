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

    protected $casts = [
        'unit_id' => 'integer',
        'created_by' => 'integer',
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
        return $this->morphMany(
            Approval::class,
            'approvalable',
            'ref_type',
            'ref_id'
        );
    }
}
