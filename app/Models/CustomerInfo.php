<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInfo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // virtual bilingual attributes (حتى تشتغل الـ mutators مع fill/create)
        'name',
        'nationality',
        'address',

        'name_en', 'name_ar',
        'nationality_en', 'nationality_ar',
        'address_en', 'address_ar',
        'passport_number',
        'birth_date',
        'gender',
        'issuance_date',
        'expiry_date',
        'email',
        'phone_number',
        'document_path',
        'booking_id',         // if exists
        'emirates_id_number',
    ];

    protected $appends = [
        'name',
        'address',
        'nationality',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'issuance_date' => 'date',
        'expiry_date' => 'date',
    ];

    protected $hidden = [
        'document_path',
        'name_en', 'name_ar',
        'address_en', 'address_ar',
        'nationality_en', 'nationality_ar',
    ];

    public function getNameAttribute(): array
    {
        return [
            'en' => $this->name_en ?? '',
            'ar' => $this->name_ar ?? '',
        ];
    }

    public function setNameAttribute($value): void
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException("name must be an array with 'en' and 'ar'");
        }

        $this->attributes['name_en'] = $value['en'] ?? '';
        $this->attributes['name_ar'] = $value['ar'] ?? '';
    }

    public function getAddressAttribute(): array
    {
        return [
            'en' => $this->address_en ?? '',
            'ar' => $this->address_ar ?? '',
        ];
    }

    public function setAddressAttribute($value): void
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException("address must be an array with 'en' and 'ar'");
        }

        $this->attributes['address_en'] = $value['en'] ?? '';
        $this->attributes['address_ar'] = $value['ar'] ?? '';
    }

    public function getNationalityAttribute(): array
    {
        return [
            'en' => $this->nationality_en ?? '',
            'ar' => $this->nationality_ar ?? '',
        ];
    }

    public function setNationalityAttribute($value): void
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException("nationality must be an array with 'en' and 'ar'");
        }

        $this->attributes['nationality_en'] = $value['en'] ?? '';
        $this->attributes['nationality_ar'] = $value['ar'] ?? '';
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
