<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerInfo extends Model
{
    protected $fillable = ['name', 'passport_number', 'birth_date', 'gender', 'place_of_birth', 'nationality', 'document_path'];
}
