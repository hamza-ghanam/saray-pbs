<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrokerProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'license_number',
        'rera_registration_number',
        'address',
        'po_box',
        'telephone',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
