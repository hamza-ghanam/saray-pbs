<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitUpdate extends Model
{
    protected $fillable = ['unit_id', 'description', 'attachment_path'];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
