<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OneTimeLink extends Model
{
    use SoftDeletes;

    protected $fillable = ['token', 'user_type', 'expired_at', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
