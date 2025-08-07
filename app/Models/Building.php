<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'location', 'status', 'ecd', 'added_by_id', 'image_path', 'plot_no'];

    protected $casts = [
        'added_by_id' => 'integer',
    ];

    protected $hidden = ['image_path'];

    public function units()
    {
        return $this->hasMany(Unit::class);
    }
}
