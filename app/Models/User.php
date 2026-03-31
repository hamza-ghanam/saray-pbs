<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'doc_type',
        'file_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function docs()
    {
        return $this->hasMany(UserDoc::class);
    }

    /**
     * Relationship: The one-time link used by this user.
     */
    public function oneTimeLink()
    {
        return $this->hasOne(OneTimeLink::class);
    }

    /**
     * Relationship: A contractor (user) can have many units.
     */
    public function contractorUnits()
    {
        return $this->hasMany(Unit::class, 'contractor_id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function dldDocuments()
    {
        return $this->hasMany(DldDocument::class, 'uploaded_by');
    }

    public function brokerProfile()
    {
        return $this->hasOne(BrokerProfile::class);
    }

    public function bookingsAsAgent()
    {
        return $this->hasMany(Booking::class, 'agent');
    }

    public function bookingsAsSaleSource()
    {
        return $this->hasMany(Booking::class, 'sale_source_id');
    }

    public function signature()
    {
        return $this->hasOne(UserSignature::class);
    }

    public function withdrawnSigningLinks()
    {
        return $this->hasMany(SigningLink::class, 'withdrawn_by');
    }

    public function brokerCommissions()
    {
        return $this->hasMany(BrokerCommission::class, 'broker_user_id');
    }

    public function recordedBrokerCommissionPayments()
    {
        return $this->hasMany(BrokerCommissionPayment::class, 'paid_by');
    }

    public function createdBrokerCommissionRates()
    {
        return $this->hasMany(BrokerCommissionRate::class, 'created_by');
    }

    public function updatedBrokerCommissionRates()
    {
        return $this->hasMany(BrokerCommissionRate::class, 'updated_by');
    }

    public function createdBrokerCommissions()
    {
        return $this->hasMany(BrokerCommission::class, 'created_by');
    }

    public function updatedBrokerCommissions()
    {
        return $this->hasMany(BrokerCommission::class, 'updated_by');
    }

    public function createdBrokerCommissionPayments()
    {
        return $this->hasMany(BrokerCommissionPayment::class, 'created_by');
    }

    public function updatedBrokerCommissionPayments()
    {
        return $this->hasMany(BrokerCommissionPayment::class, 'updated_by');
    }
}
