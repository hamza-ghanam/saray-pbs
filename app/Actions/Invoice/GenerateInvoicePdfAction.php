<?php

namespace App\Actions\Invoice;

use App\Models\Invoice;
use App\Models\GeneralSetting;
use App\Services\PdfService;
use Illuminate\Support\Facades\DB;

class GenerateInvoicePdfAction
{
    public function __construct(
        private readonly PdfService $pdfService,
    ) {}

    public function execute(Invoice $invoice): Invoice
    {
        $invoice->load([
            'booking.unit.building',
            'booking.customerInfos',
            'installmentPayment.installment',
            'installmentPayment.verifiedBy',
            'issuedBy',
        ]);

        $data = [
            'invoice'            => $invoice,
            'booking'            => $invoice->booking,
            'customerInfos'      => $invoice->booking->customerInfos,
            'installmentPayment' => $invoice->installmentPayment,
            'company'            => GeneralSetting::getGroup('company'),
        ];

        $fileName = 'invoice_' . $invoice->invoice_number . '.pdf';
        $dir      = 'bookings/invoices';

        $this->pdfService->storeWithName(
            view:     'pdf.invoice',
            data:     $data,
            dir:      $dir,
            fileName: $fileName,
        );

        $pdfPath = $dir . '/' . $fileName;

        // Save pdf_path to invoice
        return DB::transaction(function () use ($invoice, $pdfPath) {
            $invoice->update(['pdf_path' => $pdfPath]);
            return $invoice->fresh();
        });
    }
}
