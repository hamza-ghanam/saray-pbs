<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="Approval",
 *     type="object",
 *     title="Approval",
 *     required={"id","ref_id","ref_type","approved_by","approval_type","status","created_at"},
 *     @OA\Property(property="id",             type="integer", format="int64",    example=1),
 *     @OA\Property(property="ref_id",         type="integer", format="int64",    example=42),
 *     @OA\Property(property="ref_type",       type="string",  description="Model class name", example="App\\Models\\Unit"),
 *     @OA\Property(property="approved_by",    type="integer", format="int64",    example=17),
 *     @OA\Property(property="approval_type",  type="string",  example="Sales"),
 *     @OA\Property(property="status",         type="string",  example="Approved"),
 *     @OA\Property(property="created_at",     type="string",  format="date-time", example="2025-05-02T15:58:33Z")
 * )
 */
class Approval extends Model
{
    use SoftDeletes;

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
