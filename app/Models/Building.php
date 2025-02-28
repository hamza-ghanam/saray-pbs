<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $fillable = ['name', 'location', 'status', 'ecd', 'added_by_id'];

    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
