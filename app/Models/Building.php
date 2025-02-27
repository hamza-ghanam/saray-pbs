<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $fillable = ['name', 'location', 'status', 'ecd', 'crm_officer_id'];

    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
