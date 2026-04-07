<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'integer' => $this->value !== null ? (int) $this->value : null,
            'float'   => $this->value !== null ? (float) $this->value : null,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'json'    => $this->value ? json_decode($this->value, true) : null,
            default   => $this->value,
        };
    }
}
