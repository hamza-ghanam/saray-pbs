<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitUpdate extends Model
{
    use SoftDeletes;

    protected $fillable = ['unit_id', 'description', 'attachment_path'];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
