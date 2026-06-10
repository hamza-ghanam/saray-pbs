<?php

namespace App\Actions\Invoice;


use App\Actions\Invoice\GenerateInvoicePdfAction;
use App\Enums\InvoiceStatusEnum;
use App\Enums\InvoiceTypeEnum;
use App\Models\Booking;
use App\Models\InstallmentPayment;
use App\Models\Invoice;
use App\Services\InvoiceNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IssueInvoiceAction
{
    public function __construct(
        private readonly InvoiceNumberService $invoiceNumberService,
    ) {}

    /**
     * Issue an invoice for a booking confirmation or a verified payment.
     * If installment payment is provided, type defaults to payment_receipt.
     * If no installment payment, type defaults to booking_confirmation.
     */
    public function execute(
        Booking $booking,
        ?InstallmentPayment $installmentPayment = null,
        ?InvoiceTypeEnum $type = null,
        ?float $vatRate = null,
        ?string $notes = null,
    ): Invoice {
        return DB::transaction(function () use (
            $booking,
            $installmentPayment,
            $type,
            $vatRate,
            $notes,
        ) {
            // Resolve invoice type
            $invoiceType = $type ?? (
            $installmentPayment
                ? InvoiceTypeEnum::PAYMENT_RECEIPT
                : InvoiceTypeEnum::BOOKING_CONFIRMATION
            );

            // Prevent duplicate invoices for the same payment
            if ($installmentPayment) {
                $exists = Invoice::where('installment_payment_id', $installmentPayment->id)
                    ->where('status', '!=', InvoiceStatusEnum::CANCELLED)
                    ->exists();

                if ($exists) {
                    throw new RuntimeException('An invoice has already been issued for this payment.');
                }

                if (!$installmentPayment->isVerified()) {
                    throw new RuntimeException('Cannot issue invoice for an unverified payment.');
                }
            }

            // Calculate amounts
            $subtotal        = $installmentPayment
                ? (float) $installmentPayment->amount
                : (float) $booking->eoi_amount;

            $resolvedVatRate = $vatRate ?? 0.00;
            $vatAmount       = round($subtotal * ($resolvedVatRate / 100), 2);
            $total           = round($subtotal + $vatAmount, 2);

            $invoice = Invoice::create([
                'invoice_number'         => $this->invoiceNumberService->generate(),
                'booking_id'             => $booking->id,
                'installment_payment_id' => $installmentPayment?->id,
                'type'                   => $invoiceType,
                'issue_date'             => now()->toDateString(),
                'subtotal'               => $subtotal,
                'vat_rate'               => $resolvedVatRate,
                'vat_amount'             => $vatAmount,
                'total'                  => $total,
                'status'                 => InvoiceStatusEnum::ISSUED,
                'notes'                  => $notes,
                'issued_by'              => Auth::id(),
                'created_by'             => Auth::id(),
                'updated_by'             => Auth::id(),
            ]);

            // Generate PDF — if this fails, the whole transaction rolls back
            // and the invoice number is not consumed
            app(GenerateInvoicePdfAction::class)->execute($invoice);

            return $invoice->fresh();
        });
    }
}
