<?php

namespace App\Models;

use App\Enums\InvoiceStatusEnum;
use App\Enums\InvoiceTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'booking_id',
        'installment_payment_id',
        'type',
        'issue_date',
        'due_date',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total',
        'status',
        'pdf_path',
        'issued_by',
        'cancelled_at',
        'cancellation_reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date'   => 'date',
        'due_date'     => 'date',
        'subtotal'     => 'decimal:2',
        'vat_rate'     => 'decimal:2',
        'vat_amount'   => 'decimal:2',
        'total'        => 'decimal:2',
        'cancelled_at' => 'datetime',
        'status'       => InvoiceStatusEnum::class,
        'type'         => InvoiceTypeEnum::class,
    ];

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', InvoiceStatusEnum::DRAFT);
    }

    public function scopeIssued($query)
    {
        return $query->where('status', InvoiceStatusEnum::ISSUED);
    }

    // Relations
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function installmentPayment()
    {
        return $this->belongsTo(InstallmentPayment::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helpers
    public function isIssued(): bool
    {
        return $this->status === InvoiceStatusEnum::ISSUED;
    }

    public function isCancelled(): bool
    {
        return $this->status === InvoiceStatusEnum::CANCELLED;
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatusEnum::DRAFT;
    }
}
