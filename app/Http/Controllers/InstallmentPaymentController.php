<?php

namespace App\Http\Controllers;

use App\Actions\InstallmentPayment\RecordInstallmentPaymentAction;
use App\Actions\InstallmentPayment\VerifyInstallmentPaymentAction;
use App\Actions\InstallmentPayment\RejectInstallmentPaymentAction;
use App\Actions\Invoice\IssueInvoiceAction;
use App\Enums\InstallmentPaymentStatusEnum;
use App\Models\Booking;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;

class InstallmentPaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/installments",
     *     summary="List all installments (filterable)",
     *     operationId="indexInstallments",
     *     tags={"Installment Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="period", in="query", required=false, description="Shorthand date window for due_date", @OA\Schema(type="string", enum={"today","this_week","this_month"})),
     *     @OA\Parameter(name="due_from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="due_to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"pending","partially_paid","paid","overdue","waived"})),
     *     @OA\Parameter(name="pending_verification", in="query", required=false, description="Filter installments that have at least one payment awaiting verification", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="booking_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="project_id", in="query", required=false, description="Filter by building/project ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=25)),
     *     @OA\Response(response=200, description="Installments fetched successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->can('view installments')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'period'               => ['nullable', 'string', 'in:today,this_week,this_month'],
            'due_from'             => ['nullable', 'date_format:Y-m-d'],
            'due_to'               => ['nullable', 'date_format:Y-m-d', 'after_or_equal:due_from'],
            'status'               => ['nullable', 'string', 'in:pending,partially_paid,paid,overdue,waived'],
            'pending_verification' => ['nullable', 'boolean'],
            'booking_id'           => ['nullable', 'integer', 'min:1'],
            'project_id'           => ['nullable', 'integer', 'min:1'],
            'per_page'             => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $query = Installment::query()
            ->whereHas('booking')
            ->with([
                'booking:id,unit_id,status',
                'booking.unit:id,unit_no,building_id',
                'booking.unit.building:id,name',
                'booking.customerInfos:id,booking_id,name_en,name_ar,phone_number,email',
                'payments:id,installment_id,payment_date,amount,payment_method,reference_number,status,verified_at,created_at',
            ]);

        // Date window: due_from/due_to take precedence over period
        if ($request->filled('due_from') || $request->filled('due_to')) {
            if ($request->filled('due_from')) {
                $query->where('date', '>=', $request->input('due_from'));
            }
            if ($request->filled('due_to')) {
                $query->where('date', '<=', $request->input('due_to'));
            }
        } elseif ($request->filled('period')) {
            match ($request->input('period')) {
                'today'      => $query->whereDate('date', Carbon::today()),
                'this_week'  => $query->whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
                'this_month' => $query->whereBetween('date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]),
            };
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('pending_verification')) {
            $query->whereHas('payments', function ($q) {
                $q->where('status', InstallmentPaymentStatusEnum::PENDING_VERIFICATION);
            });
        }

        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->input('booking_id'));
        }

        if ($request->filled('project_id')) {
            $query->whereHas('booking.unit', function ($q) use ($request) {
                $q->where('building_id', $request->input('project_id'));
            });
        }

        $perPage = (int) $request->input('per_page', 25);

        return response()->json(
            $query->orderBy('date')->paginate($perPage),
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Get(
     *     path="/bookings/{booking}/installments",
     *     summary="List installments for a booking",
     *     operationId="listBookingInstallments",
     *     tags={"Installment Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="booking",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending","partially_paid","paid","overdue","waived"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Installments fetched successfully. Each installment includes a computed `remaining_amount` field (installment amount minus total verified payments)."
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Booking not found")
     * )
     */
    public function listInstallments(Request $request, Booking $booking)
    {
        $user = $request->user();

        if (!$user->can('view installments')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['nullable', 'string', 'in:pending,partially_paid,paid,overdue,waived'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $query = $booking->installments()
            ->with([
                'payments:id,installment_id,payment_date,amount,payment_method,reference_number,status,verified_at,created_at',
                'payments.verifiedBy:id,name',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json([
            'data' => $query->get(),
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/bookings/{booking}/installments/{installment}",
     *     summary="Show installment details",
     *     operationId="showInstallment",
     *     tags={"Installment Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="booking",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Parameter(
     *         name="installment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(response=200, description="Installment fetched successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Installment not found")
     * )
     */
    public function showInstallment(Request $request, Booking $booking, Installment $installment)
    {
        $user = $request->user();

        if (!$user->can('view installments')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($installment->booking_id !== $booking->id) {
            return response()->json(['error' => 'Installment does not belong to this booking.'], Response::HTTP_NOT_FOUND);
        }

        $installment->load([
            'payments:id,installment_id,payment_date,amount,payment_method,reference_number,status,verified_at,notes,created_at',
            'payments.verifiedBy:id,name',
            'payments.createdBy:id,name',
        ]);

        return response()->json($installment, Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/bookings/{booking}/installments/{installment}/payments",
     *     summary="Record a payment for an installment",
     *     operationId="recordInstallmentPayment",
     *     tags={"Installment Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="booking",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Parameter(
     *         name="installment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"amount","payment_date"},
     *                 @OA\Property(property="amount", type="number", format="float", example=50000),
     *                 @OA\Property(property="payment_date", type="string", format="date", example="2026-05-22"),
     *                 @OA\Property(property="payment_method", type="string", nullable=true, enum={"cash","bank_transfer","cheque","card","other"}),
     *                 @OA\Property(property="reference_number", type="string", nullable=true, example="TRX-987654"),
     *                 @OA\Property(property="receipt", type="string", format="binary", nullable=true, description="Payment receipt file (pdf, jpg, jpeg, png, max 5MB)"),
     *                 @OA\Property(property="notes", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Payment recorded successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Installment not found"),
     *     @OA\Response(response=422, description="Validation error or business rule violation")
     * )
     */
    public function recordPayment(
        Request $request,
        Booking $booking,
        Installment $installment,
        RecordInstallmentPaymentAction $recordInstallmentPaymentAction,
    ) {
        $user = $request->user();

        if (!$user->can('record installment payment')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($installment->booking_id !== $booking->id) {
            return response()->json(['error' => 'Installment does not belong to this booking.'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'amount'           => ['required', 'numeric', 'gt:0'],
            'payment_date'     => ['required', 'date'],
            'payment_method'   => ['nullable', 'string', 'in:cash,bank_transfer,cheque,card,other'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'receipt'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes'            => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $validator->validated();

        $receiptPath = null;

        try {
            if ($request->hasFile('receipt')) {
                $file        = $request->file('receipt');
                $filename    = 'installment_receipt_' . $installment->id . '_' . now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
                $receiptPath = $file->storeAs('bookings/installments/receipts', $filename, 'local');
            }

            $payment = $recordInstallmentPaymentAction->execute(
                installment:     $installment,
                amount:          (float) $validated['amount'],
                paymentDate:     $validated['payment_date'],
                paymentMethod:   $validated['payment_method'] ?? null,
                referenceNumber: $validated['reference_number'] ?? null,
                receiptPath:     $receiptPath,
                notes:           $validated['notes'] ?? null,
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            if ($receiptPath) {
                Storage::disk('local')->delete($receiptPath);
            }
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            if ($receiptPath) {
                Storage::disk('local')->delete($receiptPath);
            }
            Log::error('Record installment payment failed', [
                'installment_id' => $installment->id,
                'booking_id'     => $booking->id,
                'error'          => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $payment->load(['installment', 'createdBy:id,name']);
        $installment->refresh();

        return response()->json([
            'message'     => 'Payment recorded successfully.',
            'payment'     => $payment,
            'installment' => $installment,
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/bookings/{booking}/installments/{installment}/payments/{installmentPayment}/verify",
     *     summary="Verify an installment payment",
     *     operationId="verifyInstallmentPayment",
     *     tags={"Installment Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="booking",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Parameter(
     *         name="installment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Parameter(
     *         name="installmentPayment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(response=200, description="Payment verified successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Payment not found"),
     *     @OA\Response(response=422, description="Business rule violation")
     * )
     */
    public function verifyPayment(
        Request $request,
        Booking $booking,
        Installment $installment,
        InstallmentPayment $installmentPayment,
        VerifyInstallmentPaymentAction $verifyInstallmentPaymentAction,
    ) {
        $user = $request->user();

        if (!$user->can('verify installment payment')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($installmentPayment->installment_id !== $installment->id || $installment->booking_id !== $booking->id) {
            return response()->json(['error' => 'Payment not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $payment = $verifyInstallmentPaymentAction->execute($installmentPayment);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            Log::error('Verify installment payment failed', [
                'installment_payment_id' => $installmentPayment->id,
                'error'                  => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $payment->load(['installment', 'verifiedBy:id,name']);
        $installment->refresh();

        return response()->json([
            'message'     => 'Payment verified successfully.',
            'payment'     => $payment,
            'installment' => $installment,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/bookings/{booking}/installments/{installment}/payments/{installmentPayment}/reject",
     *     summary="Reject an installment payment",
     *     operationId="rejectInstallmentPayment",
     *     tags={"Installment Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="booking",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Parameter(
     *         name="installment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Parameter(
     *         name="installmentPayment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", nullable=true, example="Receipt is unclear.", description="Rejection reason. Stored as `rejection_reason` on the payment object; does not overwrite the payment's `notes`.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment rejected successfully. The returned `payment` object includes a `rejection_reason` field containing the supplied reason."
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Payment not found"),
     *     @OA\Response(response=422, description="Business rule violation")
     * )
     */
    public function rejectPayment(
        Request $request,
        Booking $booking,
        Installment $installment,
        InstallmentPayment $installmentPayment,
        RejectInstallmentPaymentAction $rejectInstallmentPaymentAction,
    ) {
        $user = $request->user();

        if (!$user->can('reject installment payment')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($installmentPayment->installment_id !== $installment->id || $installment->booking_id !== $booking->id) {
            return response()->json(['error' => 'Payment not found.'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $payment = $rejectInstallmentPaymentAction->execute(
                payment: $installmentPayment,
                reason:  $request->input('reason'),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            Log::error('Reject installment payment failed', [
                'installment_payment_id' => $installmentPayment->id,
                'error'                  => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Payment rejected successfully.',
            'payment' => $payment,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/bookings/{booking}/installments/{installment}/payments/{installmentPayment}/invoice",
     *     summary="Issue an invoice for a verified installment payment",
     *     operationId="issueInstallmentPaymentInvoice",
     *     tags={"Installment Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="booking",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Parameter(
     *         name="installment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Parameter(
     *         name="installmentPayment",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="vat_rate", type="number", format="float", nullable=true, example=5.00, description="VAT percentage, defaults to 0"),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Invoice issued successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Payment not found"),
     *     @OA\Response(response=422, description="Business rule violation")
     * )
     */
    public function issueInvoice(
        Request $request,
        Booking $booking,
        Installment $installment,
        InstallmentPayment $installmentPayment,
        IssueInvoiceAction $issueInvoiceAction,
    ) {
        $user = $request->user();

        if (!$user->can('issue invoice')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($installmentPayment->installment_id !== $installment->id || $installment->booking_id !== $booking->id) {
            return response()->json(['error' => 'Payment not found.'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'    => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $invoice = $issueInvoiceAction->execute(
                booking:            $booking,
                installmentPayment: $installmentPayment,
                vatRate:            $request->filled('vat_rate') ? (float) $request->input('vat_rate') : null,
                notes:              $request->input('notes'),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            Log::error('Issue invoice failed', [
                'installment_payment_id' => $installmentPayment->id,
                'booking_id'             => $booking->id,
                'error'                  => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Invoice issued successfully.',
            'invoice' => $invoice,
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/invoices/{invoice}/download",
     *     summary="Download invoice PDF",
     *     operationId="downloadInvoice",
     *     tags={"Installment Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="invoice",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(response=200, description="Invoice PDF downloaded"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Invoice or PDF not found")
     * )
     */
    public function downloadInvoice(Request $request, Invoice $invoice)
    {
        $user = $request->user();

        if (!$user->can('view invoices')) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if (!$invoice->pdf_path || !Storage::disk('local')->exists($invoice->pdf_path)) {
            return response()->json(['error' => 'Invoice PDF not found.'], Response::HTTP_NOT_FOUND);
        }

        return Storage::disk('local')->download(
            $invoice->pdf_path,
            $invoice->invoice_number . '.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
