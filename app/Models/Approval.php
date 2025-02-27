<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $fillable = [
        'ref_id',
        'ref_type',
        'approved_by',
        'approval_type',
        'status'
    ];

    /**
     * Get the parent model (either Unit or Booking) that this approval belongs to.
     *
     * Since your migration uses 'ref_type' and 'ref_id', we pass them as custom columns.
     */
    public function approvalable()
    {
        return $this->morphTo(null, 'ref_type', 'ref_id');
    }

    /**
     * Get the user that approved this record.
     */
    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
