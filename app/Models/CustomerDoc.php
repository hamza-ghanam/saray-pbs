<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerDoc extends Model
{

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_info_id',
        'doc_type',
        'file_path',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'customer_info_id' => 'integer',
    ];

    protected $hidden = [
        'file_path'
    ];

    /**
     * Get the customer that owns this document.
     */
    public function customer()
    {
        return $this->belongsTo(CustomerInfo::class);
    }
}
